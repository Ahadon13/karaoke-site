@once
    <script>
        window.myKaraokeSharedPlayer = function (initialState) {
            return {
                selectedVideo: null,
                playerOverlayOpen: false,
                playerQueueOpen: false,
                playerCanceled: false,
                queueLoading: false,
                queueStatus: '',
                fullscreenStatus: '',
                singerName: typeof localStorage !== 'undefined' ? (localStorage.getItem('mykaraokeSingerName') || '') : '',
                activeRoom: initialState.activeRoom || { code: 'ROOM' },
                queueItems: [...(initialState.queueItems || [])],
                routes: initialState.routes,
                csrfToken: initialState.csrfToken,
                playerNonce: 0,
                canceledQueueItemIds: [],
                playerChromeHidden: false,

                init() {
                    this.loadCanceledQueueItems();
                    this.loadSelectedVideo();

                    window.addEventListener('mykaraoke:play', (event) => this.playVideo(event.detail.video));
                    window.addEventListener('mykaraoke:queue', (event) => this.addToQueue(event.detail.video));
                },

                storageKey(name) {
                    return `mykaraoke:${name}:${this.activeRoom.code}`;
                },

                loadSelectedVideo() {
                    if (typeof sessionStorage === 'undefined') {
                        return;
                    }

                    try {
                        const video = JSON.parse(sessionStorage.getItem(this.storageKey('selectedVideo')) || 'null');

                        if (video && video.video_id) {
                            this.selectedVideo = video;
                            this.playerOverlayOpen = false;
                        }
                    } catch (error) {
                        this.selectedVideo = null;
                    }
                },

                persistSelectedVideo() {
                    if (typeof sessionStorage === 'undefined') {
                        return;
                    }

                    if (this.selectedVideo) {
                        sessionStorage.setItem(this.storageKey('selectedVideo'), JSON.stringify(this.selectedVideo));
                        return;
                    }

                    sessionStorage.removeItem(this.storageKey('selectedVideo'));
                },

                playerUrl() {
                    if (!this.selectedVideo) {
                        return '';
                    }

                    const params = new URLSearchParams({
                        autoplay: '1',
                        rel: '0',
                        playsinline: '1',
                        origin: window.location.origin,
                        mykaraoke: String(this.playerNonce),
                    });

                    return `https://www.youtube.com/embed/${this.selectedVideo.video_id}?${params.toString()}`;
                },

                loadCanceledQueueItems() {
                    if (typeof sessionStorage === 'undefined') {
                        return;
                    }

                    try {
                        const storedIds = JSON.parse(sessionStorage.getItem(this.storageKey('canceledQueue')) || '[]');
                        this.canceledQueueItemIds = Array.isArray(storedIds) ? storedIds.map((id) => Number(id)).filter(Boolean) : [];
                    } catch (error) {
                        this.canceledQueueItemIds = [];
                    }
                },

                persistCanceledQueueItems() {
                    if (typeof sessionStorage === 'undefined') {
                        return;
                    }

                    sessionStorage.setItem(this.storageKey('canceledQueue'), JSON.stringify(this.canceledQueueItemIds));
                },

                rememberCanceledQueueItem(item) {
                    const itemId = Number(item?.id);

                    if (!itemId || this.canceledQueueItemIds.includes(itemId)) {
                        return;
                    }

                    this.canceledQueueItemIds = [...this.canceledQueueItemIds, itemId];
                    this.persistCanceledQueueItems();
                },

                forgetCanceledQueueItem(item) {
                    const itemId = Number(item?.id);

                    if (!itemId) {
                        return;
                    }

                    this.canceledQueueItemIds = this.canceledQueueItemIds.filter((id) => id !== itemId);
                    this.persistCanceledQueueItems();
                },

                isQueueItemCanceled(item) {
                    return Boolean(item?.id && this.canceledQueueItemIds.includes(Number(item.id)));
                },

                sortedQueue(items = this.queueItems) {
                    const rank = { playing: 0, queued: 1 };

                    return [...items].sort((first, second) => {
                        const rankDifference = (rank[first.status] ?? 2) - (rank[second.status] ?? 2);

                        if (rankDifference !== 0) {
                            return rankDifference;
                        }

                        return (first.position || 0) - (second.position || 0);
                    });
                },

                queuedQueueItems() {
                    return this.sortedQueue().filter((item) => ['queued', 'playing'].includes(item.status));
                },

                activeQueuedSongs() {
                    return this.sortedQueue().filter((item) => item.status === 'queued' && !this.isQueueItemCanceled(item));
                },

                nextQueueItem() {
                    return this.activeQueuedSongs()[0] || null;
                },

                playingQueueItem() {
                    return this.queueItems.find((item) => item.status === 'playing') || null;
                },

                hasPlayerContent() {
                    return Boolean(this.selectedVideo || this.playingQueueItem() || this.activeQueuedSongs().length > 0);
                },

                hidePlayer() {
                    this.playerOverlayOpen = false;
                    this.playerQueueOpen = false;
                    this.playerChromeHidden = true;
                },

                ensurePlayerVisibility() {
                    if (this.hasPlayerContent()) {
                        return;
                    }

                    this.playerOverlayOpen = false;
                    this.playerQueueOpen = false;
                    this.playerCanceled = false;
                    this.playerChromeHidden = true;
                },

                mergeQueueItem(item) {
                    if (!item) {
                        return null;
                    }

                    if (['done', 'skipped'].includes(item.status)) {
                        this.forgetCanceledQueueItem(item);
                        this.queueItems = this.queueItems.filter((existing) => existing.id !== item.id);
                        return item;
                    }

                    this.queueItems = this.sortedQueue([
                        item,
                        ...this.queueItems.filter((existing) => existing.id !== item.id),
                    ]);
                    this.playerChromeHidden = false;

                    return item;
                },

                playVideo(video) {
                    if (!video) {
                        return;
                    }

                    this.selectedVideo = video;
                    this.playerOverlayOpen = true;
                    this.playerCanceled = false;
                    this.playerQueueOpen = false;
                    this.playerChromeHidden = false;
                    this.persistSelectedVideo();
                    this.savePlayHistory(video);
                },

                async savePlayHistory(video) {
                    if (!this.routes.playHistory) {
                        return;
                    }

                    try {
                        await fetch(this.routes.playHistory, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify({
                                video_id: video.video_id,
                                title: video.title,
                                thumbnail_url: video.thumbnail_url,
                                channel_title: video.channel_title,
                                searched_keyword: video.source_query || '',
                            }),
                        });
                    } catch (error) {
                        console.warn('Could not save karaoke play history.', error);
                    }
                },

                minimizePlayer() {
                    this.playerOverlayOpen = false;
                },

                openPlayer() {
                    if (!this.hasPlayerContent()) {
                        this.ensurePlayerVisibility();
                        return;
                    }

                    this.playerChromeHidden = false;
                    this.playerOverlayOpen = true;
                    this.playerQueueOpen = false;
                },

                cancelPlayer() {
                    if (this.selectedVideo?.id) {
                        this.rememberCanceledQueueItem(this.selectedVideo);
                    }

                    this.selectedVideo = null;
                    this.playerOverlayOpen = false;
                    this.playerQueueOpen = false;
                    this.playerCanceled = true;
                    this.playerNonce += 1;
                    this.persistSelectedVideo();
                    this.ensurePlayerVisibility();
                },

                restartSelectedVideo() {
                    if (!this.selectedVideo) {
                        return;
                    }

                    this.playerNonce += 1;
                },

                async fullscreenPlayer() {
                    const target = this.$refs.sharedPlayerSurface;

                    if (!this.selectedVideo || !target) {
                        return;
                    }

                    if (target.requestFullscreen) {
                        await target.requestFullscreen();
                    } else if (target.webkitRequestFullscreen) {
                        target.webkitRequestFullscreen();
                    }
                },

                async addToQueue(video) {
                    if (!video || this.queueLoading) {
                        return;
                    }

                    this.queueLoading = true;
                    this.queueStatus = '';

                    const singerName = this.singerName.trim() || 'Guest';

                    if (typeof localStorage !== 'undefined') {
                        localStorage.setItem('mykaraokeSingerName', singerName);
                    }

                    try {
                        const response = await fetch(this.routes.queueStore, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify({
                                room_code: this.activeRoom.code,
                                singer_name: singerName,
                                video_id: video.video_id,
                                title: video.title,
                                thumbnail_url: video.thumbnail_url,
                                channel_title: video.channel_title,
                                searched_keyword: video.source_query || '',
                            }),
                        });
                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            throw new Error(data.message || 'Could not add this song to the queue.');
                        }

                        const queueItem = this.mergeQueueItem(data.queue_item);

                        if (!this.selectedVideo || this.playerCanceled) {
                            this.playVideo(queueItem);
                        } else {
                            this.playerQueueOpen = true;
                        }
                    } catch (error) {
                        this.queueStatus = error.message || 'Could not add this song to the queue.';
                    } finally {
                        this.queueLoading = false;
                    }
                },

                async updateQueueItem(item, status) {
                    const response = await fetch(`${this.routes.queueUpdateBase}/${item.id}`, {
                        method: 'PATCH',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: JSON.stringify({ status }),
                    });
                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(data.message || 'Could not update queue.');
                    }

                    return this.mergeQueueItem(data.queue_item);
                },

                async playQueueItem(item) {
                    this.forgetCanceledQueueItem(item);
                    this.playVideo(item);
                    await this.updateQueueItem(item, 'playing');
                },

                async playNextQueuedSong() {
                    const nextItem = this.nextQueueItem();

                    if (!nextItem) {
                        this.playerQueueOpen = true;
                        return;
                    }

                    await this.playQueueItem(nextItem);
                },

                async removeQueueItem(item) {
                    const response = await fetch(`${this.routes.queueDestroyBase}/${item.id}`, {
                        method: 'DELETE',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                    });

                    if (response.ok) {
                        this.forgetCanceledQueueItem(item);
                        this.queueItems = this.queueItems.filter((existing) => existing.id !== item.id);
                        this.ensurePlayerVisibility();
                    }
                },

                togglePlayerQueue() {
                    this.playerChromeHidden = false;
                    this.playerQueueOpen = !this.playerQueueOpen;
                },
            };
        };
    </script>
@endonce

<div
    x-data="myKaraokeSharedPlayer({
        activeRoom: @js($activeRoom),
        queueItems: @js($queueItems),
        routes: {
            playHistory: @js(route('karaoke.play-history')),
            queueStore: @js(route('karaoke.queue.store')),
            queueUpdateBase: @js(url('/karaoke/queue')),
            queueDestroyBase: @js(url('/karaoke/queue')),
        },
        csrfToken: @js(csrf_token()),
    })"
    x-init="init()"
    x-cloak
>
    <section
        x-show="playerOverlayOpen && hasPlayerContent()"
        x-transition.opacity
        class="fixed inset-0 z-[180] bg-black text-white"
        aria-label="Karaoke player overlay"
    >
        <template x-if="selectedVideo && selectedVideo.thumbnail_url">
            <img x-bind:src="selectedVideo.thumbnail_url" x-bind:alt="selectedVideo.title" class="absolute inset-0 h-full w-full scale-110 object-cover opacity-25 blur-2xl">
        </template>
        <div class="absolute inset-0 bg-gradient-to-b from-[#4c1d95]/45 via-black/55 to-black"></div>

        <div class="relative flex h-dvh min-h-0 flex-col overflow-hidden">
            <div class="karaoke-overlay-header flex shrink-0 items-center justify-between gap-3 px-3 py-3 sm:px-8 sm:py-4">
                <button type="button" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-sm font-bold text-white backdrop-blur transition hover:bg-white/15 sm:px-4 sm:py-3" x-on:click="minimizePlayer">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                    <span class="hidden sm:inline">Minimize</span>
                </button>
                <div class="hidden text-center md:block">
                    <p class="karaoke-overlay-title text-3xl font-black">MyKaraoke</p>
                    <p class="karaoke-overlay-subtitle mt-1 hidden text-xs font-bold uppercase tracking-[0.22em] text-violet-100 sm:block">Karaoke preview</p>
                </div>
                <button type="button" class="grid h-11 w-11 place-items-center rounded-lg bg-white/10 text-white backdrop-blur transition hover:bg-white/15 sm:h-12 sm:w-12" x-on:click="fullscreenPlayer" x-bind:disabled="!selectedVideo" aria-label="Fullscreen player">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                        <path d="M3.25 3A1.25 1.25 0 0 0 2 4.25v3a.75.75 0 0 0 1.5 0V4.5h2.75a.75.75 0 0 0 0-1.5h-3Zm10.5 0a.75.75 0 0 0 0 1.5h2.75v2.75a.75.75 0 0 0 1.5 0v-3A1.25 1.25 0 0 0 16.75 3h-3ZM3.5 12.75a.75.75 0 0 0-1.5 0v3A1.25 1.25 0 0 0 3.25 17h3a.75.75 0 0 0 0-1.5H3.5v-2.75Zm14.5 0a.75.75 0 0 0-1.5 0v2.75h-2.75a.75.75 0 0 0 0 1.5h3A1.25 1.25 0 0 0 18 15.75v-3Z" />
                    </svg>
                </button>
            </div>

            <div x-ref="sharedPlayerSurface" class="relative min-h-0 flex-1 overflow-hidden">
                <template x-if="selectedVideo">
                    <iframe class="absolute inset-0 h-full w-full" x-bind:src="playerUrl()" x-bind:title="selectedVideo.title" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                </template>
                <div x-show="!selectedVideo" class="relative z-10 flex h-full min-h-0 items-center justify-center px-6 text-center">
                    <div>
                        <p class="text-sm font-bold uppercase text-violet-200">Player canceled</p>
                        <p class="mt-2 text-3xl font-black">Queue is still ready</p>
                    </div>
                </div>
            </div>

            <div class="shrink-0 border-t border-white/10 bg-[#202020]/95 shadow-2xl shadow-black">
                <div class="karaoke-overlay-controls grid gap-3 px-3 py-3 md:grid-cols-[minmax(0,1fr)_auto_auto] md:items-center sm:px-8 sm:py-4">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="karaoke-overlay-artwork grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-lg bg-[#4c1d95]/30 sm:h-16 sm:w-16">
                            <template x-if="selectedVideo && selectedVideo.thumbnail_url">
                                <img x-bind:src="selectedVideo.thumbnail_url" x-bind:alt="selectedVideo.title" class="h-full w-full object-cover">
                            </template>
                            <svg x-show="!selectedVideo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-7 w-7 text-violet-100" aria-hidden="true">
                                <path d="M18 3.75a.75.75 0 0 0-.9-.735l-10 2A.75.75 0 0 0 6.5 5.75v7.063A3.5 3.5 0 1 0 8 15.5V8.365l8.5-1.7v4.148A3.5 3.5 0 1 0 18 13.5V3.75Z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="line-clamp-1 text-lg font-black text-violet-100" x-text="selectedVideo ? selectedVideo.title : (nextQueueItem() ? nextQueueItem().title : 'No song selected')"></p>
                            <p class="mt-1 truncate text-sm text-neutral-400" x-text="selectedVideo ? selectedVideo.channel_title : (nextQueueItem() ? nextQueueItem().channel_title : 'Choose or queue a song to start.')"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 sm:flex sm:items-center sm:gap-3">
                        <button type="button" class="karaoke-overlay-button inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-xs font-bold text-white transition hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-50 sm:px-4 sm:py-3 sm:text-sm" x-on:click="restartSelectedVideo" x-bind:disabled="!selectedVideo">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 1 1-2.955-5.21.75.75 0 1 0 .686-1.333 7 7 0 1 0 3.758 6.635.75.75 0 1 0-1.489-.092Z" clip-rule="evenodd" />
                                <path d="M14.75 3.75a.75.75 0 0 0-.75.75v3.25h-3.25a.75.75 0 0 0 0 1.5h4A.75.75 0 0 0 15.5 8.5v-4a.75.75 0 0 0-.75-.75Z" />
                            </svg>
                            <span>Start over</span>
                        </button>
                        <button type="button" class="karaoke-overlay-button inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-xs font-bold text-white transition hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-50 sm:px-4 sm:py-3 sm:text-sm" x-on:click="playNextQueuedSong" x-bind:disabled="!nextQueueItem()">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                <path d="M4.5 4.75a.75.75 0 0 1 1.18-.614l6.5 4.75a.75.75 0 0 1 0 1.228l-6.5 4.75A.75.75 0 0 1 4.5 14.25v-9.5ZM14.75 4a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-1.5 0V4.75a.75.75 0 0 1 .75-.75Z" />
                            </svg>
                            <span>Next</span>
                        </button>
                        <button type="button" class="karaoke-overlay-button inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-xs font-bold text-white transition hover:bg-white/15 sm:px-4 sm:py-3 sm:text-sm" x-on:click="togglePlayerQueue">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                <path fill-rule="evenodd" d="M2 4.75A.75.75 0 0 1 2.75 4h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 4.75ZM2 10a.75.75 0 0 1 .75-.75h9.5a.75.75 0 0 1 0 1.5h-9.5A.75.75 0 0 1 2 10Zm.75 4.5a.75.75 0 0 0 0 1.5h14.5a.75.75 0 0 0 0-1.5H2.75Z" clip-rule="evenodd" />
                            </svg>
                            <span>Queue</span>
                        </button>
                    </div>

                    <button type="button" class="karaoke-overlay-button inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-red-500/15 px-3 py-2 text-xs font-bold text-red-100 transition hover:bg-red-500/25 sm:px-4 sm:py-3 sm:text-sm" x-on:click="cancelPlayer">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                        <span>Cancel video</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <section
        x-show="!playerOverlayOpen && hasPlayerContent() && !playerChromeHidden"
        x-transition
        class="fixed inset-x-0 bottom-0 z-[160] border-t border-white/10 bg-[#202020]/95 text-white shadow-2xl shadow-black backdrop-blur"
        aria-label="Mini karaoke player"
    >
        <div class="mx-auto grid max-w-[96rem] gap-3 px-3 py-3 md:grid-cols-[minmax(0,1fr)_auto] md:items-center sm:px-6 lg:px-8">
            <div class="flex min-w-0 items-center gap-3">
                <div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-lg bg-[#4c1d95]/30 sm:h-14 sm:w-14">
                    <template x-if="selectedVideo && selectedVideo.thumbnail_url">
                        <img x-bind:src="selectedVideo.thumbnail_url" x-bind:alt="selectedVideo.title" class="h-full w-full object-cover">
                    </template>
                </div>
                <div class="min-w-0">
                    <p class="line-clamp-1 font-black text-violet-100" x-text="selectedVideo ? selectedVideo.title : (nextQueueItem() ? nextQueueItem().title : 'Player canceled')"></p>
                    <p class="mt-1 truncate text-sm text-neutral-400" x-text="selectedVideo ? selectedVideo.channel_title : (nextQueueItem() ? 'Ready for next queue song' : 'Queue is saved for this session.')"></p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center">
                <button type="button" class="inline-flex items-center justify-center gap-1 rounded-lg bg-[#4c1d95] px-2 py-2 text-[11px] font-bold text-white shadow-lg shadow-[#4c1d95]/25 transition hover:bg-[#5b21b6] sm:px-3 sm:text-xs" x-on:click="openPlayer">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                        <path d="M3.25 4A2.25 2.25 0 0 0 1 6.25v7.5A2.25 2.25 0 0 0 3.25 16h13.5A2.25 2.25 0 0 0 19 13.75v-7.5A2.25 2.25 0 0 0 16.75 4H3.25Zm4.47 3.22a.75.75 0 0 1 .78-.06l4.5 2.25a.75.75 0 0 1 0 1.34L8.5 13a.75.75 0 0 1-1.085-.67V7.89a.75.75 0 0 1 .305-.67Z" />
                    </svg>
                    <span>Open player</span>
                </button>
                <button type="button" class="rounded-lg bg-white/10 px-2 py-2 text-[11px] font-bold text-white transition hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-50 sm:px-3 sm:text-xs" x-on:click="restartSelectedVideo" x-bind:disabled="!selectedVideo">Start over</button>
                <button type="button" class="rounded-lg bg-white/10 px-2 py-2 text-[11px] font-bold text-white transition hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-50 sm:px-3 sm:text-xs" x-on:click="playNextQueuedSong" x-bind:disabled="!nextQueueItem()">Next</button>
                <button type="button" class="rounded-lg bg-white/10 px-2 py-2 text-[11px] font-bold text-white transition hover:bg-white/15 sm:px-3 sm:text-xs" x-on:click="togglePlayerQueue">Queue</button>
                <button type="button" class="rounded-lg bg-white/10 px-2 py-2 text-[11px] font-bold text-white transition hover:bg-white/15 sm:px-3 sm:text-xs" x-on:click="hidePlayer">Hide</button>
                <button type="button" class="rounded-lg bg-red-500/15 px-2 py-2 text-[11px] font-bold text-red-100 transition hover:bg-red-500/25 sm:px-3 sm:text-xs" x-on:click="cancelPlayer" x-show="selectedVideo">Cancel</button>
            </div>
        </div>
    </section>

    <aside
        x-show="playerQueueOpen"
        x-transition
        class="fixed inset-y-0 right-0 z-[190] w-full overflow-y-auto border-l border-white/10 bg-[#101014] p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] text-white shadow-2xl shadow-black sm:max-w-md sm:p-6 [scrollbar-color:#4c1d95_transparent] [scrollbar-width:thin]"
        aria-label="Player queue panel"
    >
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-2xl font-black">Queue</h2>
            <button type="button" class="rounded-full bg-white/10 px-3 py-2 text-sm font-bold transition hover:bg-white/15" x-on:click="playerQueueOpen = false">Close</button>
        </div>
        <div class="mt-6 space-y-4">
            <input type="text" x-model="singerName" maxlength="80" placeholder="Singer name" class="min-h-12 w-full rounded-full border border-white/10 bg-[#09090d] px-4 py-3 text-sm text-white outline-none transition placeholder:text-neutral-500 focus:border-violet-300 focus:ring-2 focus:ring-violet-300/30">
            <p class="text-sm text-violet-100/80" x-show="queueStatus" x-text="queueStatus"></p>
            <template x-for="item in queuedQueueItems()" x-bind:key="`shared-queue-${item.id}`">
                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-3" x-bind:class="isQueueItemCanceled(item) ? 'opacity-60' : ''">
                    <div class="flex items-start gap-3">
                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-neutral-800">
                            <img x-show="item.thumbnail_url" x-bind:src="item.thumbnail_url" x-bind:alt="item.title" class="h-full w-full object-cover">
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-2 text-sm font-semibold" x-text="item.title"></p>
                            <p class="mt-1 truncate text-xs text-neutral-500" x-text="item.channel_title"></p>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <button type="button" class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#4c1d95]/70" x-on:click="playQueueItem(item)">Play</button>
                        <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold text-neutral-500 transition hover:bg-red-500/10 hover:text-red-100" x-on:click="removeQueueItem(item)">Remove</button>
                    </div>
                </div>
            </template>
            <div class="rounded-2xl border border-dashed border-white/10 p-4 text-sm text-neutral-500" x-show="queuedQueueItems().length === 0">No songs are queued yet.</div>
        </div>
    </aside>
</div>
