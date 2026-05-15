<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>MyKaraoke | Search and Sing</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            [x-cloak] {
                display: none !important;
            }

            .karaoke-ambient {
                background:
                    radial-gradient(circle at 16% 10%, rgba(76, 29, 149, 0.38), transparent 30rem),
                    radial-gradient(circle at 80% 22%, rgba(16, 185, 129, 0.12), transparent 26rem),
                    radial-gradient(circle at 48% 64%, rgba(76, 29, 149, 0.2), transparent 34rem),
                    #020203;
            }

            .karaoke-catalog-shell {
                background:
                    radial-gradient(circle at 18% 14%, rgba(76, 29, 149, 0.18), transparent 26rem),
                    radial-gradient(circle at 78% 48%, rgba(20, 184, 166, 0.08), transparent 26rem),
                    linear-gradient(180deg, rgba(255, 255, 255, 0.035), rgba(255, 255, 255, 0.01));
            }

            .karaoke-sweep {
                animation: karaoke-sweep 13s ease-in-out infinite alternate;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.055), transparent);
                transform: translateX(-45%) skewX(-14deg);
            }

            .stage-noise {
                background-image:
                    radial-gradient(circle at 20% 24%, rgba(255, 255, 255, 0.12) 0 0.1rem, transparent 0.12rem),
                    radial-gradient(circle at 78% 52%, rgba(168, 85, 247, 0.18) 0 0.12rem, transparent 0.12rem);
                background-size: 3rem 3rem, 4rem 4rem;
                mask-image: linear-gradient(180deg, transparent, #000 18%, #000 80%, transparent);
            }

            .player-pulse {
                animation: player-pulse 3.8s ease-in-out infinite alternate;
            }

            .catalog-hero-cover {
                background:
                    linear-gradient(90deg, rgba(76, 29, 149, 0.75), rgba(76, 29, 149, 0.46)),
                    radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.16), transparent 18rem),
                    #111114;
            }

            .genre-tile::before {
                content: '';
                position: absolute;
                left: -1.6rem;
                top: -1.6rem;
                height: 5.25rem;
                width: 5.25rem;
                border-radius: 9999px;
                background: var(--genre-accent, #4c1d95);
            }

            .genre-tile::after {
                content: '';
                position: absolute;
                left: 1.5rem;
                top: 0.55rem;
                height: 3.6rem;
                width: 3.6rem;
                border-radius: 9999px;
                background-image: radial-gradient(circle, rgba(255, 255, 255, 0.82) 0 0.11rem, transparent 0.13rem);
                background-size: 0.45rem 0.45rem;
                opacity: 0.28;
            }

            .section-tabs span:first-child {
                color: #fff;
                border-bottom-color: #fff;
            }

            .karaoke-equalizer span {
                animation: karaoke-rise 1.25s ease-in-out infinite alternate;
                transform-origin: bottom;
            }

            .karaoke-equalizer span:nth-child(2) {
                animation-delay: 0.16s;
            }

            .karaoke-equalizer span:nth-child(3) {
                animation-delay: 0.31s;
            }

            .karaoke-equalizer span:nth-child(4) {
                animation-delay: 0.08s;
            }

            .karaoke-equalizer span:nth-child(5) {
                animation-delay: 0.24s;
            }

            @keyframes karaoke-sweep {
                from {
                    transform: translateX(-48%) skewX(-14deg);
                }

                to {
                    transform: translateX(48%) skewX(-14deg);
                }
            }

            @keyframes karaoke-rise {
                from {
                    transform: scaleY(0.38);
                    opacity: 0.58;
                }

                to {
                    transform: scaleY(1);
                    opacity: 1;
                }
            }

            @keyframes player-pulse {
                from {
                    box-shadow: 0 0 0 rgba(76, 29, 149, 0);
                }

                to {
                    box-shadow: 0 0 4rem rgba(76, 29, 149, 0.22);
                }
            }
        </style>

        <script>
            window.karaokeInitialState = {
                activeRoom: @js($activeRoom),
                queueItems: @js($queueItems),
                curatedVideos: @js($curatedVideos),
                catalogVideos: @js($catalogVideos),
                trendingVideos: @js($trendingVideos),
                recentVideos: @js($recentVideos),
                favoriteVideos: @js($favoriteVideos),
                featureFilters: @js($featureFilters),
                genreFilters: @js($genreFilters),
                activeFeature: @js($activeFeature),
                activeGenre: @js($activeGenre),
                routes: {
                    search: @js(route('karaoke.search')),
                    playHistory: @js(route('karaoke.play-history')),
                    favorites: @js(route('karaoke.favorites.store')),
                    favoriteDestroyBase: @js(url('/karaoke/favorites')),
                    queueStore: @js(route('karaoke.queue.store')),
                    queueUpdateBase: @js(url('/karaoke/queue')),
                    queueDestroyBase: @js(url('/karaoke/queue')),
                    songsBase: @js(url('/songs')),
                    artistsBase: @js(url('/artists')),
                },
                csrfToken: @js(csrf_token()),
            };

            window.karaokeRoom = function () {
                return {
                    searchQuery: '',
                    results: [],
                    suggestions: [],
                    selectedVideo: null,
                    playerOverlayOpen: false,
                    playerQueueOpen: false,
                    playerSettingsOpen: false,
                    playerCanceled: false,
                    loading: false,
                    suggestionLoading: false,
                    showSuggestions: false,
                    error: '',
                    suggestionError: '',
                    microphoneEnabled: false,
                    audioDevices: [],
                    selectedAudioDeviceId: '',
                    volumeLevel: 0,
                    microphoneStatus: 'Microphone is off.',
                    fullscreenStatus: '',
                    favoriteStatus: '',
                    queueStatus: '',
                    queueLoading: false,
                    singerName: typeof localStorage !== 'undefined' ? (localStorage.getItem('mykaraokeSingerName') || '') : '',
                    lastSearchQuery: '',
                    activeRoom: window.karaokeInitialState.activeRoom || { code: 'MAINROOM' },
                    queueItems: [...(window.karaokeInitialState.queueItems || [])],
                    curatedVideos: [...(window.karaokeInitialState.curatedVideos || [])],
                    catalogVideos: [...(window.karaokeInitialState.catalogVideos || [])],
                    trendingVideos: [...(window.karaokeInitialState.trendingVideos || [])],
                    recentVideos: [...(window.karaokeInitialState.recentVideos || [])],
                    favorites: [...(window.karaokeInitialState.favoriteVideos || [])],
                    featureFilters: window.karaokeInitialState.featureFilters || {},
                    genreFilters: [...(window.karaokeInitialState.genreFilters || [])],
                    activeFeature: window.karaokeInitialState.activeFeature || 'all',
                    activeGenre: window.karaokeInitialState.activeGenre || null,
                    routes: window.karaokeInitialState.routes,
                    csrfToken: window.karaokeInitialState.csrfToken,
                    scoreDrafts: {},
                    micStream: null,
                    audioContext: null,
                    audioSource: null,
                    analyser: null,
                    meterFrame: null,
                    suggestionTimer: null,
                    suggestionAbortController: null,
                    playerNonce: 0,
                    canceledQueueItemIds: [],

                    init() {
                        this.loadAudioDevices();
                        this.loadCanceledQueueItems();
                        const initialQuery = new URLSearchParams(window.location.search).get('q');

                        if (initialQuery) {
                            this.searchQuery = initialQuery;
                            this.$nextTick(() => this.searchSongs());
                        }

                        window.addEventListener('beforeunload', () => this.stopCurrentMicrophone());
                    },

                    uniqueVideos(videos) {
                        const seen = new Set();

                        return videos.filter((video) => {
                            if (!video || !video.video_id || seen.has(video.video_id)) {
                                return false;
                            }

                            seen.add(video.video_id);

                            return true;
                        });
                    },

                    catalogSongs() {
                        return this.uniqueVideos([
                            ...this.catalogVideos,
                            ...this.trendingVideos,
                            ...this.curatedVideos,
                            ...this.recentVideos,
                            ...this.favorites,
                        ]);
                    },

                    featureOptions() {
                        return Object.entries(this.featureFilters);
                    },

                    filterByFeature(videos) {
                        if (!this.activeFeature || this.activeFeature === 'all') {
                            return videos;
                        }

                        return videos.filter((video) => (video.features || []).includes(this.activeFeature));
                    },

                    filterByGenre(videos) {
                        if (!this.activeGenre) {
                            return videos;
                        }

                        return videos.filter((video) => (video.genres || []).includes(this.activeGenre));
                    },

                    visibleSongs(videos) {
                        return this.filterByGenre(this.filterByFeature(videos));
                    },

                    topSongs() {
                        return this.visibleSongs(this.catalogSongs()).slice(0, 10);
                    },

                    latestSongs() {
                        return this.visibleSongs(this.uniqueVideos([
                            ...this.curatedVideos,
                            ...this.catalogVideos,
                            ...this.recentVideos,
                            ...this.trendingVideos,
                            ...this.favorites,
                        ])).slice(0, 8);
                    },

                    playlistSongs() {
                        return this.visibleSongs(this.catalogSongs()).slice(0, 6);
                    },

                    artistSongs() {
                        return this.visibleSongs(this.uniqueVideos([
                            ...this.favorites,
                            ...this.trendingVideos,
                            ...this.catalogVideos,
                            ...this.curatedVideos,
                            ...this.recentVideos,
                        ])).slice(0, 6);
                    },

                    genreAccent(index) {
                        return ['#ef6b5b', '#22c55e', '#eab308', '#a89a90', '#f8fafc', '#4c1d95', '#6b7280', '#dc2626', '#86efac', '#facc15', '#d4d4d8', '#a78bfa'][index % 12];
                    },

                    featureLabel(feature) {
                        return this.featureFilters[feature] || feature;
                    },

                    genreLabel(genre) {
                        return (genre || '').toUpperCase();
                    },

                    displayFeatures(video) {
                        const features = video?.features || [];

                        return features.length > 0 ? features.slice(0, 3) : ['original', 'vocals'];
                    },

                    displayGenres(video) {
                        const genres = video?.genres || [];

                        return genres.length > 0 ? genres.slice(0, 2) : ['pop'];
                    },

                    slug(value) {
                        return String(value || 'artist')
                            .toLowerCase()
                            .trim()
                            .replace(/[^a-z0-9]+/g, '-')
                            .replace(/^-+|-+$/g, '') || 'artist';
                    },

                    songUrl(video) {
                        return `${this.routes.songsBase}/${encodeURIComponent(video.video_id)}`;
                    },

                    artistUrl(video) {
                        return `${this.routes.artistsBase}/${encodeURIComponent(this.slug(video.artist_name || video.channel_title || 'artist'))}`;
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
                            const storedIds = JSON.parse(sessionStorage.getItem(`mykaraokeCanceledQueue:${this.activeRoom.code}`) || '[]');
                            this.canceledQueueItemIds = Array.isArray(storedIds) ? storedIds.map((id) => Number(id)).filter(Boolean) : [];
                        } catch (error) {
                            this.canceledQueueItemIds = [];
                        }
                    },

                    persistCanceledQueueItems() {
                        if (typeof sessionStorage === 'undefined') {
                            return;
                        }

                        sessionStorage.setItem(`mykaraokeCanceledQueue:${this.activeRoom.code}`, JSON.stringify(this.canceledQueueItemIds));
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

                    activeQueuedSongs() {
                        return this.queuedSongs().filter((item) => !this.isQueueItemCanceled(item));
                    },

                    readyQueueCount() {
                        return this.activeQueuedSongs().length;
                    },

                    openPlayerFromHeader() {
                        if (this.selectedVideo) {
                            this.playerOverlayOpen = true;
                            return;
                        }

                        this.primaryPlayerAction();
                    },

                    minimizePlayer() {
                        this.playerOverlayOpen = false;
                    },

                    cancelPlayer() {
                        if (this.selectedVideo?.id) {
                            this.rememberCanceledQueueItem(this.selectedVideo);
                        }

                        this.selectedVideo = null;
                        this.playerOverlayOpen = false;
                        this.playerQueueOpen = false;
                        this.playerSettingsOpen = false;
                        this.playerCanceled = true;
                        this.playerNonce += 1;
                        this.fullscreenStatus = 'Video canceled. Your queue is still saved for this session.';
                    },

                    primaryPlayerLabel() {
                        if (this.selectedVideo) {
                            return 'Open player';
                        }

                        if (this.nextQueueItem()) {
                            return this.playerCanceled ? 'Play next' : 'Start queue';
                        }

                        return 'Choose song';
                    },

                    async primaryPlayerAction() {
                        if (this.selectedVideo) {
                            this.playerOverlayOpen = true;
                            return;
                        }

                        if (this.nextQueueItem()) {
                            await this.playNextQueuedSong();
                            return;
                        }

                        this.queueStatus = this.playerCanceled
                            ? 'The video was canceled. Add another song or restore a queued song to continue.'
                            : 'Choose a song before opening the player.';
                    },

                    togglePlayerQueue() {
                        this.playerQueueOpen = !this.playerQueueOpen;
                        this.playerSettingsOpen = false;
                    },

                    togglePlayerSettings() {
                        this.playerSettingsOpen = !this.playerSettingsOpen;
                        this.playerQueueOpen = false;
                    },

                    restartSelectedVideo() {
                        if (!this.selectedVideo) {
                            this.fullscreenStatus = 'Choose a song before restarting.';
                            return;
                        }

                        this.playerNonce += 1;
                        this.fullscreenStatus = 'Restarting song from the beginning.';
                    },

                    async fullscreenPlayer() {
                        this.fullscreenStatus = '';

                        if (!this.selectedVideo) {
                            this.fullscreenStatus = 'Choose a video before going fullscreen.';
                            return;
                        }

                        const target = this.$refs.playerSurface;

                        try {
                            if (target.requestFullscreen) {
                                await target.requestFullscreen();
                            } else if (target.webkitRequestFullscreen) {
                                target.webkitRequestFullscreen();
                            } else if (target.msRequestFullscreen) {
                                target.msRequestFullscreen();
                            } else {
                                this.fullscreenStatus = 'Fullscreen is not supported in this browser.';
                            }
                        } catch (error) {
                            this.fullscreenStatus = 'Could not open fullscreen mode.';
                        }
                    },

                    async searchSongs() {
                        const query = this.searchQuery.trim();

                        this.error = '';
                        this.suggestionError = '';
                        this.showSuggestions = false;

                        if (!query) {
                            this.error = 'Type a song title or artist to search.';
                            return;
                        }

                        this.loading = true;
                        this.results = [];
                        this.lastSearchQuery = query;

                        try {
                            const response = await fetch(`${this.routes.search}?q=${encodeURIComponent(query)}`, {
                                headers: {
                                    Accept: 'application/json',
                                },
                            });
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                const validationMessage = data.errors && data.errors.q ? data.errors.q[0] : null;
                                throw new Error(validationMessage || data.message || 'Could not search songs right now.');
                            }

                            this.results = data.results || [];
                            this.catalogVideos = this.uniqueVideos([
                                ...this.results,
                                ...this.catalogVideos,
                            ]).slice(0, 48);
                            this.suggestions = this.results.slice(0, 8);
                            this.showSuggestions = true;

                            if (this.results.length === 0) {
                                this.suggestionError = 'No embeddable karaoke videos found. Try another song or artist.';
                            }
                        } catch (error) {
                            this.error = error.message || 'Could not search songs right now.';
                            this.suggestionError = this.error;
                            this.showSuggestions = true;
                        } finally {
                            this.loading = false;
                        }
                    },

                    clearSearch() {
                        if (this.suggestionTimer) {
                            clearTimeout(this.suggestionTimer);
                            this.suggestionTimer = null;
                        }

                        if (this.suggestionAbortController) {
                            this.suggestionAbortController.abort();
                            this.suggestionAbortController = null;
                        }

                        this.searchQuery = '';
                        this.results = [];
                        this.suggestions = [];
                        this.error = '';
                        this.suggestionError = '';
                        this.suggestionLoading = false;
                        this.showSuggestions = false;
                    },

                    scheduleSuggestions() {
                        const query = this.searchQuery.trim();
                        this.suggestionError = '';

                        if (this.suggestionTimer) {
                            clearTimeout(this.suggestionTimer);
                        }

                        if (query.length < 2) {
                            this.suggestions = [];
                            this.suggestionLoading = false;
                            this.showSuggestions = false;
                            return;
                        }

                        this.showSuggestions = true;
                        this.suggestionTimer = setTimeout(() => this.loadSuggestions(query), 450);
                    },

                    async loadSuggestions(query) {
                        if (this.suggestionAbortController) {
                            this.suggestionAbortController.abort();
                        }

                        this.suggestionAbortController = new AbortController();
                        this.suggestionLoading = true;
                        this.showSuggestions = true;

                        try {
                            const response = await fetch(`${this.routes.search}?q=${encodeURIComponent(query)}`, {
                                headers: {
                                    Accept: 'application/json',
                                },
                                signal: this.suggestionAbortController.signal,
                            });
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                const validationMessage = data.errors && data.errors.q ? data.errors.q[0] : null;
                                throw new Error(validationMessage || data.message || 'Could not load suggestions.');
                            }

                            this.suggestions = (data.results || []).slice(0, 6);
                        } catch (error) {
                            if (error.name !== 'AbortError') {
                                this.suggestionError = error.message || 'Could not load suggestions.';
                                this.suggestions = [];
                            }
                        } finally {
                            this.suggestionLoading = false;
                        }
                    },

                    chooseSuggestion(video) {
                        this.lastSearchQuery = video.title;
                        this.clearSearch();
                        this.playVideo(video);
                    },

                    async queueSuggestion(video) {
                        this.lastSearchQuery = video.title;
                        await this.addToQueue(video);
                        this.clearSearch();
                    },

                    playVideo(video) {
                        this.selectedVideo = video;
                        this.playerOverlayOpen = true;
                        this.playerCanceled = false;
                        this.playerQueueOpen = false;
                        this.playerSettingsOpen = false;
                        this.error = '';
                        this.savePlayHistory(video);
                    },

                    async savePlayHistory(video) {
                        try {
                            const response = await fetch(this.routes.playHistory, {
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
                                    searched_keyword: this.lastSearchQuery || this.searchQuery.trim(),
                                }),
                            });

                            const data = await response.json().catch(() => ({}));

                            if (response.ok && data.history) {
                                this.mergeHistory(data.history);
                            }
                        } catch (error) {
                            console.warn('Could not save karaoke play history.', error);
                        }
                    },

                    mergeHistory(history) {
                        const normalize = (video) => ({
                            video_id: video.video_id,
                            title: video.title,
                            thumbnail_url: video.thumbnail_url,
                            channel_title: video.channel_title,
                            played_count: video.played_count || 1,
                            last_played_at: video.last_played_at || null,
                        });
                        const incoming = normalize(history);
                        const withoutIncoming = (videos) => videos.filter((video) => video.video_id !== incoming.video_id);

                        this.recentVideos = [incoming, ...withoutIncoming(this.recentVideos)].slice(0, 8);
                        this.trendingVideos = [incoming, ...withoutIncoming(this.trendingVideos)]
                            .sort((a, b) => (b.played_count || 0) - (a.played_count || 0))
                            .slice(0, 8);
                    },

                    normalizeQueueItem(item) {
                        return {
                            id: item.id,
                            singer_name: item.singer_name || 'Guest',
                            video_id: item.video_id,
                            title: item.title,
                            thumbnail_url: item.thumbnail_url,
                            channel_title: item.channel_title,
                            searched_keyword: item.searched_keyword,
                            position: item.position || 1,
                            status: item.status || 'queued',
                            score: item.score ?? null,
                            started_at: item.started_at || null,
                            finished_at: item.finished_at || null,
                        };
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

                    playingQueueItem() {
                        return this.queueItems.find((item) => item.status === 'playing') || null;
                    },

                    queuedSongs() {
                        return this.sortedQueue().filter((item) => item.status === 'queued');
                    },

                    queuedQueueItems() {
                        return this.sortedQueue().filter((item) => ['queued', 'playing'].includes(item.status));
                    },

                    nextQueueItem() {
                        return this.activeQueuedSongs()[0] || null;
                    },

                    nextSingerName() {
                        return this.nextQueueItem()?.singer_name || 'No singer queued';
                    },

                    isQueued(video) {
                        if (!video) {
                            return false;
                        }

                        return this.queueItems.some((item) => item.video_id === video.video_id && ['queued', 'playing'].includes(item.status));
                    },

                    mergeQueueItem(item) {
                        const queueItem = this.normalizeQueueItem(item);

                        if (['done', 'skipped'].includes(queueItem.status)) {
                            this.forgetCanceledQueueItem(queueItem);
                            this.queueItems = this.queueItems.filter((existing) => existing.id !== queueItem.id);

                            return queueItem;
                        }

                        this.queueItems = this.sortedQueue([
                            queueItem,
                            ...this.queueItems.filter((existing) => existing.id !== queueItem.id),
                        ]);

                        return queueItem;
                    },

                    roomShareUrl() {
                        const url = new URL(window.location.href);
                        url.searchParams.set('room', this.activeRoom.code || 'MAINROOM');

                        return url.toString();
                    },

                    async copyRoomLink() {
                        this.queueStatus = '';

                        try {
                            await navigator.clipboard.writeText(this.roomShareUrl());
                            this.queueStatus = 'Room link copied.';
                        } catch (error) {
                            this.queueStatus = 'Room link is ready in the address bar.';
                            window.history.replaceState({}, '', this.roomShareUrl());
                        }
                    },

                    async addToQueue(video) {
                        this.queueStatus = '';

                        if (!video) {
                            this.queueStatus = 'Choose a song before adding it to the queue.';
                            return;
                        }

                        const singerName = this.singerName.trim() || 'Guest';

                        if (typeof localStorage !== 'undefined') {
                            localStorage.setItem('mykaraokeSingerName', singerName);
                        }

                        this.queueLoading = true;

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
                                    searched_keyword: this.lastSearchQuery || this.searchQuery.trim(),
                                }),
                            });
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                throw new Error(data.message || 'Could not add this song to the queue.');
                            }

                            if (data.queue_item) {
                                const queueItem = this.mergeQueueItem(data.queue_item);
                                this.queueStatus = `${video.title} was added for ${singerName}.`;

                                if (!this.selectedVideo || this.playerCanceled) {
                                    this.playVideo(queueItem);
                                }
                            }
                        } catch (error) {
                            this.queueStatus = error.message || 'Could not add this song to the queue.';
                        } finally {
                            this.queueLoading = false;
                        }
                    },

                    async updateQueueItem(item, status, score = null) {
                        this.queueStatus = '';

                        try {
                            const payload = { status };

                            if (score !== null) {
                                payload.score = Math.max(0, Math.min(100, Number(score) || 0));
                            }

                            const response = await fetch(`${this.routes.queueUpdateBase}/${item.id}`, {
                                method: 'PATCH',
                                headers: {
                                    Accept: 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': this.csrfToken,
                                },
                                body: JSON.stringify(payload),
                            });
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                throw new Error(data.message || 'Could not update the queue.');
                            }

                            if (data.queue_item) {
                                const updatedItem = this.mergeQueueItem(data.queue_item);

                                if (status === 'done') {
                                    this.queueStatus = `${updatedItem.singer_name} finished with ${updatedItem.score || 0} points.`;
                                } else if (status === 'skipped') {
                                    this.queueStatus = `${updatedItem.singer_name}'s song was skipped.`;
                                }

                                return updatedItem;
                            }
                        } catch (error) {
                            this.queueStatus = error.message || 'Could not update the queue.';
                        }

                        return null;
                    },

                    async playQueueItem(item) {
                        this.forgetCanceledQueueItem(item);
                        this.playVideo(item);
                        await this.updateQueueItem(item, 'playing');
                    },

                    async playNextQueuedSong() {
                        const nextItem = this.nextQueueItem();

                        if (!nextItem) {
                            this.queueStatus = 'No songs are waiting in the queue yet.';
                            return;
                        }

                        const currentItem = this.playingQueueItem();

                        if (currentItem && currentItem.id !== nextItem.id) {
                            await this.updateQueueItem(currentItem, 'skipped');
                        }

                        await this.playQueueItem(nextItem);
                        this.queueStatus = `Playing ${nextItem.title} for ${nextItem.singer_name}.`;
                    },

                    async finishQueueItem(item) {
                        const score = this.scoreDrafts[item.id] ?? 85;
                        this.forgetCanceledQueueItem(item);
                        await this.updateQueueItem(item, 'done', score);
                        delete this.scoreDrafts[item.id];
                    },

                    async skipQueueItem(item) {
                        this.forgetCanceledQueueItem(item);
                        await this.updateQueueItem(item, 'skipped');
                    },

                    async removeQueueItem(item) {
                        this.queueStatus = '';
                        this.forgetCanceledQueueItem(item);

                        try {
                            const response = await fetch(`${this.routes.queueDestroyBase}/${item.id}`, {
                                method: 'DELETE',
                                headers: {
                                    Accept: 'application/json',
                                    'X-CSRF-TOKEN': this.csrfToken,
                                },
                            });
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                throw new Error(data.message || 'Could not remove this song.');
                            }

                            this.queueItems = this.queueItems.filter((existing) => existing.id !== item.id);
                            this.queueStatus = 'Removed from the queue.';
                        } catch (error) {
                            this.queueStatus = error.message || 'Could not remove this song.';
                        }
                    },

                    normalizeFavorite(video) {
                        return {
                            video_id: video.video_id,
                            title: video.title,
                            thumbnail_url: video.thumbnail_url,
                            channel_title: video.channel_title,
                            rating: video.rating || null,
                            favorited_at: video.favorited_at || null,
                        };
                    },

                    favoriteFor(video) {
                        const videoId = typeof video === 'string' ? video : video?.video_id;

                        return this.favorites.find((favorite) => favorite.video_id === videoId) || null;
                    },

                    isFavorite(video) {
                        return Boolean(this.favoriteFor(video));
                    },

                    favoriteRating(video) {
                        return this.favoriteFor(video)?.rating || 0;
                    },

                    async toggleFavorite(video) {
                        if (this.isFavorite(video)) {
                            await this.removeFavorite(video.video_id);
                            return;
                        }

                        await this.saveFavorite(video);
                    },

                    async saveFavorite(video, rating = null) {
                        this.favoriteStatus = '';

                        try {
                            const response = await fetch(this.routes.favorites, {
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
                                    rating,
                                }),
                            });
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                throw new Error(data.message || 'Could not save favorite.');
                            }

                            if (data.favorite) {
                                const favorite = this.normalizeFavorite(data.favorite);
                                this.favorites = [
                                    favorite,
                                    ...this.favorites.filter((item) => item.video_id !== favorite.video_id),
                                ].slice(0, 12);
                                this.favoriteStatus = rating ? 'Rating saved.' : 'Added to favorites.';
                            }
                        } catch (error) {
                            this.favoriteStatus = error.message || 'Could not save favorite.';
                        }
                    },

                    async removeFavorite(videoId) {
                        this.favoriteStatus = '';

                        try {
                            const response = await fetch(`${this.routes.favoriteDestroyBase}/${encodeURIComponent(videoId)}`, {
                                method: 'DELETE',
                                headers: {
                                    Accept: 'application/json',
                                    'X-CSRF-TOKEN': this.csrfToken,
                                },
                            });
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                throw new Error(data.message || 'Could not remove favorite.');
                            }

                            this.favorites = this.favorites.filter((favorite) => favorite.video_id !== videoId);
                            this.favoriteStatus = 'Removed from favorites.';
                        } catch (error) {
                            this.favoriteStatus = error.message || 'Could not remove favorite.';
                        }
                    },

                    async rateVideo(video, rating) {
                        await this.saveFavorite(video, rating);
                    },

                    async enableMicrophone() {
                        this.error = '';

                        if (!window.isSecureContext) {
                            this.microphoneStatus = 'Microphone access needs HTTPS or localhost.';
                            return;
                        }

                        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                            this.microphoneStatus = 'This browser does not support microphone access.';
                            return;
                        }

                        this.stopCurrentMicrophone();

                        try {
                            const constraints = {
                                audio: this.selectedAudioDeviceId
                                    ? { deviceId: { exact: this.selectedAudioDeviceId } }
                                    : true,
                            };
                            const stream = await navigator.mediaDevices.getUserMedia(constraints);

                            this.microphoneEnabled = true;
                            this.microphoneStatus = 'Microphone is listening.';
                            await this.loadAudioDevices(true);
                            this.startVolumeMeter(stream);
                        } catch (error) {
                            this.microphoneEnabled = false;
                            this.volumeLevel = 0;

                            if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                                this.microphoneStatus = 'Microphone permission was denied.';
                            } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                                this.microphoneStatus = 'No microphone was found.';
                            } else if (error.name === 'NotReadableError') {
                                this.microphoneStatus = 'Microphone is busy in another app.';
                            } else {
                                this.microphoneStatus = 'Could not start the microphone.';
                            }
                        }
                    },

                    async loadAudioDevices(showStatus = false) {
                        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
                            if (showStatus) {
                                this.microphoneStatus = 'This browser cannot list microphones.';
                            }

                            return;
                        }

                        try {
                            const devices = await navigator.mediaDevices.enumerateDevices();
                            this.audioDevices = devices.filter((device) => device.kind === 'audioinput');

                            if (this.audioDevices.length === 0 && showStatus) {
                                this.microphoneStatus = 'No microphone was found.';
                            }
                        } catch (error) {
                            if (showStatus) {
                                this.microphoneStatus = 'Could not load microphone devices.';
                            }
                        }
                    },

                    startVolumeMeter(stream) {
                        const AudioContext = window.AudioContext || window.webkitAudioContext;

                        this.micStream = stream;

                        if (!AudioContext) {
                            this.microphoneStatus = 'Microphone is on, but the volume meter is unavailable.';
                            return;
                        }

                        this.audioContext = new AudioContext();
                        this.analyser = this.audioContext.createAnalyser();
                        this.analyser.fftSize = 256;
                        this.audioSource = this.audioContext.createMediaStreamSource(stream);
                        this.audioSource.connect(this.analyser);

                        const samples = new Uint8Array(this.analyser.fftSize);
                        const tick = () => {
                            this.analyser.getByteTimeDomainData(samples);

                            let sum = 0;

                            for (let index = 0; index < samples.length; index += 1) {
                                const centered = (samples[index] - 128) / 128;
                                sum += centered * centered;
                            }

                            const rms = Math.sqrt(sum / samples.length);
                            this.volumeLevel = Math.min(100, Math.round(rms * 180));
                            this.meterFrame = requestAnimationFrame(tick);
                        };

                        tick();
                    },

                    stopCurrentMicrophone() {
                        if (this.meterFrame) {
                            cancelAnimationFrame(this.meterFrame);
                            this.meterFrame = null;
                        }

                        if (this.micStream) {
                            this.micStream.getTracks().forEach((track) => track.stop());
                            this.micStream = null;
                        }

                        if (this.audioContext) {
                            this.audioContext.close();
                            this.audioContext = null;
                        }

                        this.audioSource = null;
                        this.analyser = null;
                        this.volumeLevel = 0;
                    },
                };
            };
        </script>
    </head>
    <body class="min-h-screen overflow-x-hidden bg-black text-white antialiased">
        <div class="music-note-field" aria-hidden="true">
            <span class="music-note" style="--note-left: 6%; --note-size: 1.7rem; --note-duration: 20s; --note-delay: -3s; --note-drift: 2.5rem;">&#9834;</span>
            <span class="music-note" style="--note-left: 31%; --note-size: 1.6rem; --note-duration: 19s; --note-delay: -6s; --note-drift: 3rem;">&#9834;</span>
            <span class="music-note" style="--note-left: 63%; --note-size: 1.9rem; --note-duration: 22s; --note-delay: -9s; --note-drift: 2rem;">&#9834;</span>
            <span class="music-note" style="--note-left: 92%; --note-size: 1.8rem; --note-duration: 21s; --note-delay: -5s; --note-drift: 3rem;">&#9834;</span>
        </div>

        <div x-data="karaokeRoom()" x-cloak class="relative z-10 min-h-screen">
            <header class="sticky top-0 z-[100] border-b border-white/10 bg-[#04150f]/95 backdrop-blur-2xl">
                <nav class="mx-auto grid min-h-20 w-full max-w-[96rem] gap-3 px-4 py-3 md:grid-cols-[13rem_minmax(0,1fr)_auto] md:items-center sm:px-6 lg:px-8">
                    <a href="{{ url('/') }}" class="text-2xl font-black tracking-tight text-white transition hover:text-violet-200">
                        MyKaraoke
                    </a>

                    <form class="relative z-[110] flex min-w-0 items-center gap-2" x-on:submit.prevent="searchSongs" x-on:click.outside="showSuggestions = false">
                        <a href="{{ url('/') }}" class="hidden h-12 w-12 shrink-0 place-items-center rounded-full bg-white/[0.08] text-violet-200 transition hover:bg-[#4c1d95] hover:text-white md:grid" aria-label="Home">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                <path fill-rule="evenodd" d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v5a2 2 0 0 1-2 2h-2.5a.5.5 0 0 1-.5-.5V13a1 1 0 1 0-2 0v4.5a.5.5 0 0 1-.5.5H6a2 2 0 0 1-2-2v-5H3a1 1 0 0 1-.707-1.707l7-7Z" clip-rule="evenodd" />
                            </svg>
                        </a>

                        <div class="relative min-w-0 flex-1">
                            <label class="sr-only" for="song-search">Search your song</label>
                            <span class="pointer-events-none absolute left-5 top-1/2 z-10 -translate-y-1/2 text-neutral-400">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.473 9.765l2.631 2.631a.75.75 0 1 0 1.06-1.06l-2.63-2.632A5.5 5.5 0 0 0 9 3.5Zm-4 5.5a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            <input
                                id="song-search"
                                type="search"
                                x-model="searchQuery"
                                x-on:input="scheduleSuggestions"
                                x-on:focus="searchQuery.trim().length >= 2 && (showSuggestions = true)"
                                maxlength="100"
                                autocomplete="off"
                                placeholder="Search songs, artists and playlists"
                                class="min-h-12 w-full rounded-full border border-white/10 bg-white/[0.08] py-3 pl-12 pr-24 text-sm font-semibold text-white outline-none transition placeholder:text-neutral-400 focus:border-violet-300 focus:ring-2 focus:ring-violet-300/30"
                            >
                            <button
                                type="button"
                                class="absolute right-12 top-1/2 z-10 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-full text-neutral-400 transition duration-300 hover:bg-white/10 hover:text-white"
                                x-show="searchQuery.length > 0"
                                x-transition.opacity
                                x-on:click="clearSearch"
                                aria-label="Clear search"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                                </svg>
                            </button>
                            <button type="submit" class="absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-full bg-[#4c1d95] text-white transition hover:bg-[#5b21b6]" aria-label="Search">
                                <svg x-show="!loading" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M15.22 9.47a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 1 1-1.06-1.06L12.94 10.7H5.25a.75.75 0 0 1 0-1.5h7.69L9.91 6.18a.75.75 0 0 1 1.06-1.06l4.25 4.25Z" clip-rule="evenodd" />
                                </svg>
                                <span x-show="loading" class="h-3 w-3 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                            </button>

                            <div
                                class="absolute left-0 right-0 top-[calc(100%+0.75rem)] z-[120] overflow-hidden rounded-2xl border border-violet-300/20 bg-[#101014]/95 shadow-2xl shadow-black/70 backdrop-blur-2xl"
                                x-show="showSuggestions && (suggestionLoading || suggestions.length > 0 || suggestionError)"
                                x-transition
                            >
                                <div class="flex items-center justify-between border-b border-white/10 px-4 py-3 text-xs font-semibold text-violet-200">
                                    <span>Related songs</span>
                                    <button type="button" class="text-neutral-400 transition hover:text-white" x-on:click="clearSearch">Close</button>
                                </div>
                                <div class="grid max-h-[30rem] gap-3 overflow-y-auto p-3 sm:grid-cols-2 lg:grid-cols-3 [scrollbar-color:#4c1d95_transparent] [scrollbar-width:thin]">
                                    <template x-for="video in suggestions" x-bind:key="`suggestion-${video.video_id}`">
                                        <article class="group rounded-xl border border-white/10 bg-white/[0.04] p-2 transition duration-300 hover:-translate-y-1 hover:border-violet-300/50 hover:bg-[#4c1d95]/20">
                                            <button type="button" class="block w-full text-left" x-on:pointerdown.prevent.stop="chooseSuggestion(video)">
                                                <div class="relative aspect-video overflow-hidden rounded-lg bg-neutral-800">
                                                    <img x-show="video.thumbnail_url" x-bind:src="video.thumbnail_url" x-bind:alt="video.title" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                                    <span class="absolute bottom-2 right-2 grid h-9 w-9 place-items-center rounded-full bg-[#4c1d95] text-white shadow-lg shadow-black/30 transition duration-300 group-hover:scale-105">
                                                        <span class="sr-only">Play suggestion</span>
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                                            <path d="M6.3 2.841A1.5 1.5 0 0 0 4 4.11v11.78a1.5 1.5 0 0 0 2.3 1.269l9.344-5.89a1.5 1.5 0 0 0 0-2.538L6.3 2.84Z" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <p class="mt-3 line-clamp-2 min-h-10 text-sm font-bold text-white" x-text="video.title"></p>
                                                <p class="mt-1 truncate text-xs text-neutral-400" x-text="video.channel_title"></p>
                                            </button>
                                            <button type="button" class="mt-3 w-full rounded-full border border-violet-300/25 bg-white/10 px-3 py-2 text-xs font-bold text-white transition duration-300 hover:border-violet-200 hover:bg-[#4c1d95]/60 disabled:cursor-not-allowed disabled:opacity-50" x-on:pointerdown.prevent.stop="queueSuggestion(video)" x-bind:disabled="queueLoading">
                                                <span aria-hidden="true">Queue</span>
                                                <span class="sr-only">Queue suggestion</span>
                                            </button>
                                        </article>
                                    </template>
                                    <div class="rounded-xl border border-white/10 bg-white/[0.04] p-4 text-sm text-neutral-400 sm:col-span-2 lg:col-span-3" x-show="suggestionLoading">Finding related songs...</div>
                                    <div class="rounded-xl border border-red-300/20 bg-red-500/10 p-4 text-sm text-red-100 sm:col-span-2 lg:col-span-3" x-show="suggestionError" x-text="suggestionError"></div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="hidden rounded-full border border-violet-300/25 bg-white/10 px-4 py-3.5 text-xs font-bold text-white transition duration-300 hover:border-violet-200 hover:bg-[#4c1d95]/60 disabled:cursor-not-allowed disabled:opacity-50 lg:block" x-on:click="addToQueue(selectedVideo)" x-bind:disabled="!selectedVideo || queueLoading">
                            Queue selected
                        </button>
                    </form>

                    <div class="flex items-center justify-end gap-3 text-sm">
                        <button type="button" class="hidden rounded-full text-neutral-300 transition hover:text-white md:inline-flex" x-on:click="copyRoomLink">Room {{ $activeRoom->code }}</button>
                        <button type="button" class="rounded-full bg-[#4c1d95] px-4 py-2 text-xs font-bold text-white shadow-lg shadow-[#4c1d95]/30 transition hover:-translate-y-0.5 hover:bg-[#5b21b6]" x-on:click="openPlayerFromHeader">Open player</button>
                    </div>
                </nav>
            </header>

            <main class="mx-auto w-full max-w-[96rem] px-4 py-8 sm:px-6 lg:px-8">
                <section class="catalog-hero-cover relative overflow-hidden rounded-lg border border-white/10 px-6 py-12 shadow-2xl shadow-black/30 md:px-12 md:py-16" data-reveal>
                    <div class="absolute inset-0 grid grid-cols-4 gap-1 opacity-20 md:grid-cols-8">
                        <template x-for="video in catalogSongs().slice(0, 16)" x-bind:key="`hero-${video.video_id}`">
                            <img x-show="video.thumbnail_url" x-bind:src="video.thumbnail_url" x-bind:alt="video.title" class="h-full min-h-24 w-full object-cover">
                        </template>
                    </div>
                    <div class="absolute inset-0 bg-[#2e1065]/70"></div>
                    <div class="relative mx-auto max-w-4xl text-center">
                        <p class="text-sm font-bold text-violet-100">Search your song</p>
                        <h1 class="mt-3 text-4xl font-black leading-tight sm:text-5xl">Welcome to the new era of karaoke</h1>
                        <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-violet-50/90">
                            Search karaoke songs, start the player, save favorites, and keep your queue moving with no hassle.
                        </p>
                        <button type="button" class="mt-8 inline-flex rounded-md bg-[#4c1d95] px-6 py-3 text-sm font-bold text-white shadow-xl shadow-[#4c1d95]/35 transition hover:-translate-y-0.5 hover:bg-[#5b21b6]" x-on:click="openPlayerFromHeader">
                            Start singing
                        </button>
                    </div>
                </section>

                <section id="songs" class="mt-12" data-reveal>
                    <div class="flex flex-wrap items-center gap-8">
                        <h2 class="text-2xl font-black">Top songs</h2>
                        <div class="flex flex-wrap gap-6 text-sm font-bold text-neutral-500">
                            <template x-for="[key, label] in featureOptions()" x-bind:key="`top-filter-${key}`">
                                <button type="button" class="border-b pb-2 transition hover:text-white" x-bind:class="activeFeature === key ? 'border-white text-white' : 'border-transparent'" x-on:click="activeFeature = key" x-text="label"></button>
                            </template>
                        </div>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-lg bg-[#0d0d0f] shadow-2xl shadow-black/25">
                        <div x-show="topSongs().length > 0">
                            <template x-for="(video, index) in topSongs()" x-bind:key="`top-song-${video.video_id}`">
                                <div x-data="{ menuOpen: false }" class="relative grid gap-4 border-b border-white/[0.04] px-4 py-4 transition duration-300 hover:bg-white/[0.04] md:grid-cols-[3rem_minmax(0,1fr)_minmax(14rem,0.7fr)_auto] md:items-center">
                                    <div class="text-center text-sm text-neutral-500" x-text="index + 1"></div>
                                    <button type="button" class="flex min-w-0 items-center gap-4 text-left" x-on:click="playVideo(video)">
                                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-neutral-900">
                                            <img x-show="video.thumbnail_url" x-bind:src="video.thumbnail_url" x-bind:alt="video.title" class="h-full w-full object-cover">
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-bold text-white" x-text="video.title"></p>
                                            <p class="mt-1 truncate text-sm text-neutral-400" x-text="video.channel_title"></p>
                                        </div>
                                    </button>
                                    <div class="flex flex-wrap gap-2 text-xs font-semibold text-neutral-500">
                                        <template x-for="feature in displayFeatures(video)" x-bind:key="`top-feature-${video.video_id}-${feature}`">
                                            <span class="rounded-full px-3 py-1" x-bind:class="feature === 'original' ? 'bg-[#4c1d95] text-violet-50' : 'bg-white/10 text-neutral-300'" x-text="featureLabel(feature)"></span>
                                        </template>
                                        <template x-for="genre in displayGenres(video)" x-bind:key="`top-genre-${video.video_id}-${genre}`">
                                            <span x-text="genreLabel(genre)"></span>
                                        </template>
                                    </div>
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" class="rounded-full bg-white/10 px-3 py-2 text-xs font-bold text-white transition hover:bg-[#4c1d95]/70" x-on:click.stop="playVideo(video)">Play</button>
                                        <button type="button" class="rounded-full px-3 py-2 text-xs font-bold text-violet-100 transition hover:bg-[#4c1d95]/50" x-on:click.stop="addToQueue(video)">Queue</button>
                                        <button type="button" class="grid h-9 w-9 place-items-center rounded-full bg-white/10 text-white transition hover:bg-white/15" x-on:click.stop="menuOpen = !menuOpen" aria-label="More song actions">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                                <path d="M3 10a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm5.5 0a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM15 8.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div x-show="menuOpen" x-on:click.outside="menuOpen = false" x-transition class="absolute right-4 top-16 z-30 w-48 overflow-hidden rounded-xl border border-white/10 bg-[#151518] py-2 text-sm shadow-2xl shadow-black">
                                        <a x-bind:href="songUrl(video)" class="block px-4 py-2 transition hover:bg-white/10">View song page</a>
                                        <a x-bind:href="artistUrl(video)" class="block px-4 py-2 transition hover:bg-white/10">View artist page</a>
                                        <button type="button" class="block w-full px-4 py-2 text-left transition hover:bg-white/10" x-on:click="addToQueue(video); menuOpen = false">Add to queue</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="p-6 text-sm text-neutral-500" x-show="topSongs().length === 0">
                            Search or play a song to start building your top songs list.
                        </div>
                    </div>
                </section>

                <section class="mt-12 grid gap-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]" id="room-controls">
                    <section class="rounded-lg border border-white/10 bg-[#0d0d0f] p-5 shadow-2xl shadow-black/30">
                        <div>
                            <p class="text-sm font-semibold text-violet-200">Room setup</p>
                            <h2 class="mt-1 text-2xl font-black">Singer and mic</h2>
                            <p class="mt-1 text-sm text-neutral-400">Set the singer name once, then queue from any song card.</p>
                        </div>

                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="singer-name" class="mb-2 block text-xs font-semibold uppercase text-neutral-400">Singer name</label>
                                <input id="singer-name" type="text" x-model="singerName" maxlength="80" placeholder="Guest" class="min-h-12 w-full rounded-full border border-white/10 bg-[#09090d] px-4 py-3 text-sm text-white outline-none transition placeholder:text-neutral-500 focus:border-violet-300 focus:ring-2 focus:ring-violet-300/30">
                            </div>
                            <button type="button" class="min-h-12 rounded-full bg-[#4c1d95] px-5 py-3 font-semibold text-white shadow-lg shadow-[#4c1d95]/30 transition hover:-translate-y-0.5 hover:bg-[#5b21b6]" x-on:click="enableMicrophone" x-text="microphoneEnabled ? 'Restart Mic' : 'Enable Mic'"></button>
                            <div>
                                <label for="audio-device" class="sr-only">Input device</label>
                                <select id="audio-device" x-model="selectedAudioDeviceId" x-on:change="microphoneEnabled && enableMicrophone()" class="min-h-12 w-full rounded-full border border-white/10 bg-[#09090d] px-4 py-3 text-sm text-white outline-none focus:border-violet-300 focus:ring-2 focus:ring-violet-300/30">
                                    <option value="">Default input</option>
                                    <template x-for="(device, index) in audioDevices" x-bind:key="device.deviceId || index">
                                        <option x-bind:value="device.deviceId" x-text="device.label || `Microphone ${index + 1}`"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 rounded-2xl border border-white/10 bg-white/[0.04] p-3">
                            <div class="flex items-center justify-between gap-3 text-xs">
                                <span class="text-neutral-400" x-text="microphoneStatus"></span>
                                <span class="text-neutral-100" x-text="`${volumeLevel}%`"></span>
                            </div>
                            <div class="mt-3 h-4 overflow-hidden rounded-full bg-neutral-800">
                                <div class="h-full rounded-full bg-violet-300 transition-[width] duration-75" x-bind:style="`width: ${volumeLevel}%`"></div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-white/10 bg-[#0d0d0f] p-5 shadow-2xl shadow-black/30">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-violet-200">Session queue</p>
                                <h2 class="mt-1 text-2xl font-black">Ready to sing</h2>
                                <p class="mt-1 text-sm text-neutral-400">Canceled videos stay listed, but the player skips them for this browser session.</p>
                            </div>
                            <button type="button" class="rounded-full bg-[#4c1d95] px-4 py-2 text-xs font-bold text-white shadow-lg shadow-[#4c1d95]/25 transition hover:bg-[#5b21b6] disabled:cursor-not-allowed disabled:opacity-50" x-on:click="primaryPlayerAction" x-bind:disabled="!nextQueueItem() && !selectedVideo" x-text="primaryPlayerLabel()"></button>
                        </div>

                        <p class="mt-3 text-sm text-violet-100/80" x-show="queueStatus" x-text="queueStatus"></p>
                        <div class="mt-5 flex items-center justify-between gap-3 text-sm">
                            <span class="font-semibold text-white">Waiting</span>
                            <span class="text-neutral-400" x-text="`${readyQueueCount()} ready / ${queuedQueueItems().length} saved`"></span>
                        </div>
                        <div class="mt-4 max-h-80 space-y-3 overflow-y-auto pr-2 [scrollbar-color:#4c1d95_transparent] [scrollbar-width:thin]" x-show="queuedQueueItems().length > 0">
                            <template x-for="item in queuedQueueItems()" x-bind:key="`room-queue-${item.id}`">
                                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-3 transition duration-300 hover:-translate-y-0.5 hover:border-violet-300/60 hover:bg-[#4c1d95]/20" x-bind:class="isQueueItemCanceled(item) ? 'opacity-60' : ''">
                                    <div class="flex items-start gap-3">
                                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-neutral-800">
                                            <img x-show="item.thumbnail_url" x-bind:src="item.thumbnail_url" x-bind:alt="item.title" class="h-full w-full object-cover">
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full bg-black/30 px-2 py-1 text-[11px] font-semibold uppercase text-violet-100" x-text="isQueueItemCanceled(item) ? 'canceled here' : item.status"></span>
                                                <span class="truncate text-xs text-neutral-400" x-text="item.singer_name"></span>
                                            </div>
                                            <p class="mt-1 line-clamp-2 text-sm font-semibold text-white" x-text="item.title"></p>
                                            <p class="mt-1 truncate text-xs text-neutral-500" x-text="item.channel_title"></p>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <button type="button" class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#4c1d95]/70" x-on:click="playQueueItem(item)">Play</button>
                                        <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold text-neutral-300 transition hover:bg-white/10 hover:text-white" x-on:click="skipQueueItem(item)">Skip</button>
                                        <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold text-neutral-500 transition hover:bg-red-500/10 hover:text-red-100" x-on:click="removeQueueItem(item)">Remove</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="mt-4 rounded-2xl border border-dashed border-white/10 p-4 text-sm text-neutral-500" x-show="queuedQueueItems().length === 0">Add a song from the catalog to start the singer rotation.</div>
                    </section>
                </section>

                <section class="mt-12">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-2xl font-black">Top genres</h2>
                        <button type="button" class="text-sm font-semibold text-neutral-400 transition hover:text-white" x-on:click="activeGenre = null">Show all</button>
                    </div>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                        <template x-for="(genre, index) in genreFilters" x-bind:key="genre.key">
                            <button
                                type="button"
                                class="genre-tile relative overflow-hidden rounded-lg bg-[#1c1c1f] p-10 text-center font-black transition duration-300 hover:-translate-y-1 hover:bg-[#4c1d95]/35"
                                x-bind:class="activeGenre === genre.key ? 'ring-2 ring-violet-300' : ''"
                                x-bind:style="`--genre-accent:${genreAccent(index)};`"
                                x-on:click="activeGenre = activeGenre === genre.key ? null : genre.key"
                            >
                                <span class="relative z-10" x-text="genre.label"></span>
                            </button>
                        </template>
                    </div>
                </section>

                <section class="mt-12 space-y-12">
                    <section>
                        <div class="flex flex-wrap items-center gap-8">
                            <h2 class="text-2xl font-black">Latest additions</h2>
                            <div class="flex flex-wrap gap-6 text-sm font-bold text-neutral-500">
                                <template x-for="[key, label] in featureOptions()" x-bind:key="`latest-filter-${key}`">
                                    <button type="button" class="border-b pb-2 transition hover:text-white" x-bind:class="activeFeature === key ? 'border-white text-white' : 'border-transparent'" x-on:click="activeFeature = key" x-text="label"></button>
                                </template>
                            </div>
                        </div>
                        <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6" x-show="latestSongs().length > 0">
                            <template x-for="video in latestSongs()" x-bind:key="`latest-${video.video_id}`">
                                <article x-data="{ menuOpen: false }" class="group relative rounded-lg bg-[#111114] p-3 transition duration-300 hover:-translate-y-2 hover:bg-white/[0.08]">
                                    <button type="button" class="block w-full text-left" x-on:click="playVideo(video)">
                                        <div class="relative aspect-square overflow-hidden rounded-md bg-neutral-900">
                                            <img x-show="video.thumbnail_url" x-bind:src="video.thumbnail_url" x-bind:alt="video.title" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                        </div>
                                        <p class="mt-4 line-clamp-2 min-h-12 text-base font-bold text-white" x-text="video.title"></p>
                                        <p class="mt-1 truncate text-sm text-neutral-400" x-text="video.channel_title"></p>
                                    </button>
                                    <div class="mt-3 flex items-center gap-2">
                                        <button type="button" class="rounded-full border border-violet-300/25 bg-white/10 px-3 py-2 text-xs font-bold text-white transition hover:bg-[#4c1d95]/60" x-on:click.stop="addToQueue(video)">Queue</button>
                                        <button type="button" class="grid h-9 w-9 place-items-center rounded-full bg-white/10 text-white transition hover:bg-white/15" x-on:click.stop="menuOpen = !menuOpen" aria-label="More song actions">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                                <path d="M3 10a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm5.5 0a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM15 8.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div x-show="menuOpen" x-on:click.outside="menuOpen = false" x-transition class="absolute right-3 top-14 z-30 w-48 overflow-hidden rounded-xl border border-white/10 bg-[#151518] py-2 text-sm shadow-2xl shadow-black">
                                        <a x-bind:href="songUrl(video)" class="block px-4 py-2 transition hover:bg-white/10">View song page</a>
                                        <a x-bind:href="artistUrl(video)" class="block px-4 py-2 transition hover:bg-white/10">View artist page</a>
                                    </div>
                                </article>
                            </template>
                        </div>
                        <div class="mt-5 rounded-lg border border-dashed border-white/10 p-6 text-sm text-neutral-500" x-show="latestSongs().length === 0">Latest additions will appear after you search or play songs.</div>
                    </section>

                    <section>
                        <div class="flex flex-wrap items-center gap-8">
                            <h2 class="text-2xl font-black">Trending songs</h2>
                            <div class="flex flex-wrap gap-6 text-sm font-bold text-neutral-500">
                                <template x-for="[key, label] in featureOptions()" x-bind:key="`trending-filter-${key}`">
                                    <button type="button" class="border-b pb-2 transition hover:text-white" x-bind:class="activeFeature === key ? 'border-white text-white' : 'border-transparent'" x-on:click="activeFeature = key" x-text="label"></button>
                                </template>
                            </div>
                        </div>
                        <div class="mt-5 overflow-hidden rounded-lg bg-[#0d0d0f]" x-show="visibleSongs(trendingVideos).length > 0">
                            <template x-for="video in visibleSongs(trendingVideos)" x-bind:key="`trend-${video.video_id}`">
                                <div x-data="{ menuOpen: false }" class="relative grid gap-4 border-b border-white/[0.04] px-4 py-4 transition hover:bg-white/[0.04] md:grid-cols-[minmax(0,1fr)_minmax(14rem,0.7fr)_auto] md:items-center">
                                    <button type="button" class="flex min-w-0 items-center gap-4 text-left" x-on:click="playVideo(video)">
                                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-neutral-900">
                                            <img x-show="video.thumbnail_url" x-bind:src="video.thumbnail_url" x-bind:alt="video.title" class="h-full w-full object-cover">
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-bold text-white" x-text="video.title"></p>
                                            <p class="mt-1 truncate text-sm text-neutral-400" x-text="video.channel_title"></p>
                                        </div>
                                    </button>
                                    <div class="flex flex-wrap gap-2 text-xs font-semibold text-neutral-500">
                                        <template x-for="feature in displayFeatures(video)" x-bind:key="`trend-feature-${video.video_id}-${feature}`">
                                            <span class="rounded-full px-3 py-1" x-bind:class="feature === 'original' ? 'bg-[#4c1d95] text-violet-50' : 'bg-white/10 text-neutral-300'" x-text="featureLabel(feature)"></span>
                                        </template>
                                        <template x-for="genre in displayGenres(video)" x-bind:key="`trend-genre-${video.video_id}-${genre}`">
                                            <span x-text="genreLabel(genre)"></span>
                                        </template>
                                    </div>
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" class="rounded-full bg-white/10 px-3 py-2 text-xs font-bold text-white transition hover:bg-[#4c1d95]/70" x-on:click.stop="addToQueue(video)">Queue</button>
                                        <button type="button" class="grid h-9 w-9 place-items-center rounded-full bg-white/10 text-white transition hover:bg-white/15" x-on:click.stop="menuOpen = !menuOpen" aria-label="More song actions">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                                <path d="M3 10a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm5.5 0a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM15 8.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div x-show="menuOpen" x-on:click.outside="menuOpen = false" x-transition class="absolute right-4 top-14 z-30 w-48 overflow-hidden rounded-xl border border-white/10 bg-[#151518] py-2 text-sm shadow-2xl shadow-black">
                                        <a x-bind:href="songUrl(video)" class="block px-4 py-2 transition hover:bg-white/10">View song page</a>
                                        <a x-bind:href="artistUrl(video)" class="block px-4 py-2 transition hover:bg-white/10">View artist page</a>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="mt-5 rounded-lg border border-dashed border-white/10 p-6 text-sm text-neutral-500" x-show="visibleSongs(trendingVideos).length === 0">No trending songs yet.</div>
                    </section>

                    <section>
                        <div class="flex flex-wrap items-center gap-8">
                            <h2 class="text-2xl font-black">Playlists</h2>
                            <div class="section-tabs flex flex-wrap gap-8 text-sm font-bold text-neutral-500">
                                <span class="border-b pb-2">Recommended</span>
                                <span>Latest additions</span>
                            </div>
                        </div>
                        <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6" x-show="playlistSongs().length > 0">
                            <template x-for="video in playlistSongs()" x-bind:key="`playlist-${video.video_id}`">
                                <article class="group rounded-lg bg-[#111114] p-3 transition duration-300 hover:-translate-y-2 hover:bg-white/[0.08]">
                                    <button type="button" class="block w-full text-left" x-on:click="playVideo(video)">
                                        <div class="relative aspect-[4/3] overflow-hidden rounded-md bg-neutral-900">
                                            <img x-show="video.thumbnail_url" x-bind:src="video.thumbnail_url" x-bind:alt="video.title" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                            <span class="absolute bottom-2 left-2 rounded-full bg-[#4c1d95]/90 px-3 py-1 text-xs font-bold">Karaoke</span>
                                        </div>
                                        <p class="mt-4 line-clamp-2 text-base font-bold text-white" x-text="video.title"></p>
                                    </button>
                                </article>
                            </template>
                        </div>
                        <div class="mt-5 rounded-lg border border-dashed border-white/10 p-6 text-sm text-neutral-500" x-show="playlistSongs().length === 0">Playlist recommendations will appear here.</div>
                    </section>

                    <section>
                        <div class="flex flex-wrap items-center gap-8">
                            <h2 class="text-2xl font-black">Artists</h2>
                            <div class="section-tabs flex flex-wrap gap-8 text-sm font-bold text-neutral-500">
                                <span class="border-b pb-2">Top</span>
                                <span>Trending</span>
                                <span>Recently added</span>
                            </div>
                        </div>
                        <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6" x-show="artistSongs().length > 0">
                            <template x-for="video in artistSongs()" x-bind:key="`artist-${video.video_id}`">
                                <button type="button" class="group rounded-lg bg-[#111114] p-4 text-left transition duration-300 hover:-translate-y-2 hover:bg-white/[0.08]" x-on:click="playVideo(video)">
                                    <div class="mx-auto aspect-square w-full max-w-44 overflow-hidden rounded-full bg-neutral-900">
                                        <img x-show="video.thumbnail_url" x-bind:src="video.thumbnail_url" x-bind:alt="video.channel_title" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                    </div>
                                    <p class="mt-4 truncate text-center font-bold text-white" x-text="video.channel_title || 'Karaoke artist'"></p>
                                </button>
                            </template>
                        </div>
                        <div class="mt-5 rounded-lg border border-dashed border-white/10 p-6 text-sm text-neutral-500" x-show="artistSongs().length === 0">Artists will appear after songs are saved or played.</div>
                    </section>

                    <section>
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-2xl font-black">Favorites</h2>
                            <span class="text-sm text-violet-200" x-text="`${favorites.length} saved`"></span>
                        </div>
                        <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6" x-show="favorites.length > 0">
                            <template x-for="video in favorites" x-bind:key="`favorite-${video.video_id}`">
                                <article class="group rounded-lg border border-violet-300/20 bg-[#4c1d95]/15 p-3 transition duration-300 hover:-translate-y-2 hover:bg-[#4c1d95]/25">
                                    <button type="button" class="block w-full text-left" x-on:click="playVideo(video)">
                                        <div class="relative aspect-square overflow-hidden rounded-md bg-neutral-900">
                                            <img x-show="video.thumbnail_url" x-bind:src="video.thumbnail_url" x-bind:alt="video.title" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                        </div>
                                        <p class="mt-4 line-clamp-2 min-h-12 text-base font-bold text-white" x-text="video.title"></p>
                                        <p class="mt-1 truncate text-sm text-neutral-400" x-text="video.channel_title"></p>
                                    </button>
                                    <button type="button" class="mt-3 rounded-full border border-violet-300/25 bg-white/10 px-3 py-2 text-xs font-bold text-white transition hover:bg-[#4c1d95]/60" x-on:click="removeFavorite(video.video_id)">Remove</button>
                                </article>
                            </template>
                        </div>
                        <div class="mt-5 rounded-lg border border-dashed border-violet-300/20 bg-[#4c1d95]/10 p-6 text-sm text-violet-100/60" x-show="favorites.length === 0">Favorite songs and ratings will appear here.</div>
                    </section>
                </section>
            </main>

            <footer class="mt-20 border-t border-white/10 bg-[#0b0b0c] px-4 py-14 sm:px-6 lg:px-8">
                <div class="mx-auto grid max-w-[96rem] gap-10 md:grid-cols-[minmax(0,1fr)_repeat(3,minmax(0,12rem))]">
                    <div>
                        <p class="text-3xl font-black">MyKaraoke</p>
                        <p class="mt-4 max-w-sm text-sm leading-6 text-neutral-500">Search, queue, and sing karaoke from one simple room.</p>
                    </div>
                    <div>
                        <p class="text-sm font-bold uppercase text-neutral-400">Karaoke</p>
                        <div class="mt-4 space-y-3 text-sm text-neutral-400">
                            <button type="button" class="block transition hover:text-white" x-on:click="openPlayerFromHeader">Open player</button>
                            <a href="#songs" class="block transition hover:text-white">Find songs</a>
                            <a href="#song-search" class="block transition hover:text-white">Search catalog</a>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-bold uppercase text-neutral-400">Room</p>
                        <div class="mt-4 space-y-3 text-sm text-neutral-400">
                            <button type="button" class="block transition hover:text-white" x-on:click="copyRoomLink">Share room</button>
                            <span class="block">Singer queue</span>
                            <span class="block">Microphone meter</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-bold uppercase text-neutral-400">Status</p>
                        <div class="mt-4 flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.06] px-3 py-2 text-sm text-neutral-300">
                            <span class="h-2.5 w-2.5 rounded-full bg-violet-300 shadow-[0_0_18px_rgba(167,139,250,0.75)]"></span>
                            <span>Ready for requests</span>
                        </div>
                    </div>
                </div>
            </footer>

            <section
                x-show="playerOverlayOpen"
                x-transition.opacity
                class="fixed inset-0 z-[180] bg-black text-white"
                aria-label="Karaoke player overlay"
            >
                <template x-if="selectedVideo && selectedVideo.thumbnail_url">
                    <img x-bind:src="selectedVideo.thumbnail_url" x-bind:alt="selectedVideo.title" class="absolute inset-0 h-full w-full scale-110 object-cover opacity-25 blur-2xl">
                </template>
                <div class="absolute inset-0 bg-gradient-to-b from-[#4c1d95]/45 via-black/55 to-black"></div>

                <div class="relative flex min-h-dvh flex-col">
                    <div class="flex items-center justify-between gap-3 px-4 py-4 sm:px-8">
                        <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-3 text-sm font-bold text-white backdrop-blur transition hover:bg-white/15" x-on:click="minimizePlayer">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                            <span>Minimize</span>
                        </button>
                        <div class="text-center">
                            <p class="text-3xl font-black">MyKaraoke</p>
                            <p class="mt-1 text-xs font-bold uppercase tracking-[0.22em] text-violet-100">Karaoke preview</p>
                        </div>
                        <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-3 text-sm font-bold text-white backdrop-blur transition hover:bg-white/15" x-on:click="fullscreenPlayer" x-bind:disabled="!selectedVideo" aria-label="Fullscreen player">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                <path d="M3.25 3A1.25 1.25 0 0 0 2 4.25v3a.75.75 0 0 0 1.5 0V4.5h2.75a.75.75 0 0 0 0-1.5h-3Zm10.5 0a.75.75 0 0 0 0 1.5h2.75v2.75a.75.75 0 0 0 1.5 0v-3A1.25 1.25 0 0 0 16.75 3h-3ZM3.5 12.75a.75.75 0 0 0-1.5 0v3A1.25 1.25 0 0 0 3.25 17h3a.75.75 0 0 0 0-1.5H3.5v-2.75Zm14.5 0a.75.75 0 0 0-1.5 0v2.75h-2.75a.75.75 0 0 0 0 1.5h3A1.25 1.25 0 0 0 18 15.75v-3Z" />
                            </svg>
                            <span>Fullscreen</span>
                        </button>
                    </div>

                    <div x-ref="playerSurface" class="relative min-h-[22rem] flex-1 overflow-hidden">
                        <div class="stage-noise absolute inset-0 opacity-50"></div>
                        <template x-if="selectedVideo">
                            <iframe class="absolute inset-0 h-full w-full" x-bind:src="playerUrl()" x-bind:title="selectedVideo.title" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                        </template>
                        <div x-show="!selectedVideo" class="relative z-10 flex h-full min-h-[22rem] items-center justify-center px-6 text-center">
                            <div>
                                <p class="text-sm font-bold uppercase text-violet-200">Player canceled</p>
                                <p class="mt-2 text-3xl font-black">Queue is still ready</p>
                                <p class="mt-2 text-sm text-neutral-300">Use Play next to continue with the next active queue song.</p>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-white/10 bg-[#202020]/95 shadow-2xl shadow-black">
                        <div class="grid gap-4 px-4 py-4 lg:grid-cols-[minmax(0,1fr)_auto_auto] lg:items-center sm:px-8">
                            <div class="flex min-w-0 items-center gap-4">
                                <div class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-lg bg-[#4c1d95]/30">
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

                            <div class="grid grid-cols-3 gap-3 sm:flex sm:items-center">
                                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white/10 px-4 py-3 text-sm font-bold text-white transition hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-50" x-on:click="restartSelectedVideo" x-bind:disabled="!selectedVideo">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 1 1-2.955-5.21.75.75 0 1 0 .686-1.333 7 7 0 1 0 3.758 6.635.75.75 0 1 0-1.489-.092Z" clip-rule="evenodd" />
                                        <path d="M14.75 3.75a.75.75 0 0 0-.75.75v3.25h-3.25a.75.75 0 0 0 0 1.5h4A.75.75 0 0 0 15.5 8.5v-4a.75.75 0 0 0-.75-.75Z" />
                                    </svg>
                                    <span>Start over</span>
                                </button>
                                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white/10 px-4 py-3 text-sm font-bold text-white transition hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-50" x-on:click="playNextQueuedSong" x-bind:disabled="!nextQueueItem()">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                        <path d="M4.5 4.75a.75.75 0 0 1 1.18-.614l6.5 4.75a.75.75 0 0 1 0 1.228l-6.5 4.75A.75.75 0 0 1 4.5 14.25v-9.5ZM14.75 4a.75.75 0 0 1 .75.75v10.5a.75.75 0 0 1-1.5 0V4.75a.75.75 0 0 1 .75-.75Z" />
                                    </svg>
                                    <span>Next</span>
                                </button>
                                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white/10 px-4 py-3 text-sm font-bold text-white transition hover:bg-white/15" x-on:click="togglePlayerQueue">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M2 4.75A.75.75 0 0 1 2.75 4h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 4.75ZM2 10a.75.75 0 0 1 .75-.75h9.5a.75.75 0 0 1 0 1.5h-9.5A.75.75 0 0 1 2 10Zm.75 4.5a.75.75 0 0 0 0 1.5h14.5a.75.75 0 0 0 0-1.5H2.75Z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Queue</span>
                                </button>
                            </div>

                            <div class="flex sm:items-center">
                                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-500/15 px-4 py-3 text-sm font-bold text-red-100 transition hover:bg-red-500/25" x-on:click="cancelPlayer">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                                    </svg>
                                    <span>Cancel video</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                x-show="!playerOverlayOpen && (selectedVideo || playerCanceled || queuedQueueItems().length > 0)"
                x-transition
                class="fixed inset-x-0 bottom-0 z-[160] border-t border-white/10 bg-[#202020]/95 text-white shadow-2xl shadow-black backdrop-blur"
                aria-label="Mini karaoke player"
            >
                <div class="mx-auto grid max-w-[96rem] gap-4 px-4 py-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-lg bg-[#4c1d95]/30">
                            <template x-if="selectedVideo && selectedVideo.thumbnail_url">
                                <img x-bind:src="selectedVideo.thumbnail_url" x-bind:alt="selectedVideo.title" class="h-full w-full object-cover">
                            </template>
                            <svg x-show="!selectedVideo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6 text-violet-100" aria-hidden="true">
                                <path d="M18 3.75a.75.75 0 0 0-.9-.735l-10 2A.75.75 0 0 0 6.5 5.75v7.063A3.5 3.5 0 1 0 8 15.5V8.365l8.5-1.7v4.148A3.5 3.5 0 1 0 18 13.5V3.75Z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="line-clamp-1 font-black text-violet-100" x-text="selectedVideo ? selectedVideo.title : (nextQueueItem() ? nextQueueItem().title : 'Player canceled')"></p>
                            <p class="mt-1 truncate text-sm text-neutral-400" x-text="selectedVideo ? selectedVideo.channel_title : (nextQueueItem() ? 'Ready for next queue song' : 'Queue is saved for this session.')"></p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="rounded-lg bg-white/10 px-3 py-2 text-xs font-bold text-white transition hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-50" x-on:click="restartSelectedVideo" x-bind:disabled="!selectedVideo">Start over</button>
                        <button type="button" class="rounded-lg bg-white/10 px-3 py-2 text-xs font-bold text-white transition hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-50" x-on:click="playNextQueuedSong" x-bind:disabled="!nextQueueItem()">Next</button>
                        <button type="button" class="rounded-lg bg-white/10 px-3 py-2 text-xs font-bold text-white transition hover:bg-white/15" x-on:click="togglePlayerQueue">Queue</button>
                        <button type="button" class="rounded-lg bg-red-500/15 px-3 py-2 text-xs font-bold text-red-100 transition hover:bg-red-500/25" x-on:click="cancelPlayer" x-show="selectedVideo">Cancel video</button>
                    </div>
                </div>
            </section>

            <aside
                x-show="playerQueueOpen"
                x-transition
                class="fixed inset-y-0 right-0 z-[190] w-full max-w-md overflow-y-auto border-l border-white/10 bg-[#101014] p-5 text-white shadow-2xl shadow-black sm:p-6 [scrollbar-color:#4c1d95_transparent] [scrollbar-width:thin]"
                aria-label="Player side panel"
            >
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-2xl font-black">Queue</h2>
                    <button type="button" class="rounded-full bg-white/10 px-3 py-2 text-sm font-bold transition hover:bg-white/15" x-on:click="playerQueueOpen = false">Close</button>
                </div>

                <div class="mt-6 space-y-4">
                    <label for="drawer-singer-name" class="text-sm font-medium text-neutral-300">Singer name</label>
                    <input id="drawer-singer-name" type="text" x-model="singerName" maxlength="80" placeholder="Guest" class="min-h-12 w-full rounded-full border border-white/10 bg-[#09090d] px-4 py-3 text-sm text-white outline-none transition placeholder:text-neutral-500 focus:border-violet-300 focus:ring-2 focus:ring-violet-300/30">
                    <p class="text-sm text-violet-100/80" x-show="queueStatus" x-text="queueStatus"></p>
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold">Waiting</span>
                        <span class="text-neutral-400" x-text="`${readyQueueCount()} ready / ${queuedQueueItems().length} saved`"></span>
                    </div>
                    <template x-for="item in queuedQueueItems()" x-bind:key="`drawer-queue-${item.id}`">
                        <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-3" x-bind:class="isQueueItemCanceled(item) ? 'opacity-60' : ''">
                            <div class="flex items-start gap-3">
                                <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-neutral-800">
                                    <img x-show="item.thumbnail_url" x-bind:src="item.thumbnail_url" x-bind:alt="item.title" class="h-full w-full object-cover">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-black/30 px-2 py-1 text-[11px] font-semibold uppercase text-violet-100" x-text="isQueueItemCanceled(item) ? 'canceled here' : item.status"></span>
                                        <span class="truncate text-xs text-neutral-400" x-text="item.singer_name"></span>
                                    </div>
                                    <p class="mt-1 line-clamp-2 text-sm font-semibold" x-text="item.title"></p>
                                    <p class="mt-1 truncate text-xs text-neutral-500" x-text="item.channel_title"></p>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <button type="button" class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#4c1d95]/70" x-on:click="playQueueItem(item)">Play</button>
                                <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold text-neutral-300 transition hover:bg-white/10 hover:text-white" x-on:click="skipQueueItem(item)">Skip</button>
                                <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold text-neutral-500 transition hover:bg-red-500/10 hover:text-red-100" x-on:click="removeQueueItem(item)">Remove</button>
                            </div>
                        </div>
                    </template>
                    <div class="rounded-2xl border border-dashed border-white/10 p-4 text-sm text-neutral-500" x-show="queuedQueueItems().length === 0">No songs are queued yet.</div>
                </div>
            </aside>
        </div>
    </body>
</html>
