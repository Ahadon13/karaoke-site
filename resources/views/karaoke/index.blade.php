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
                    radial-gradient(circle at 12% 18%, rgba(76, 29, 149, 0.42), transparent 28rem),
                    radial-gradient(circle at 86% 8%, rgba(34, 211, 238, 0.18), transparent 24rem),
                    linear-gradient(180deg, #08050f 0%, #0f0820 42%, #08050f 100%);
            }

            .karaoke-sweep {
                animation: karaoke-sweep 11s ease-in-out infinite alternate;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.09), transparent);
                transform: translateX(-45%) skewX(-14deg);
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
        </style>

        <script>
            window.karaokeInitialState = {
                activeRoom: @js($activeRoom),
                queueItems: @js($queueItems),
                scoreboardItems: @js($scoreboardItems),
                curatedVideos: @js($curatedVideos),
                trendingVideos: @js($trendingVideos),
                recentVideos: @js($recentVideos),
                favoriteVideos: @js($favoriteVideos),
                routes: {
                    search: @js(route('karaoke.search')),
                    playHistory: @js(route('karaoke.play-history')),
                    favorites: @js(route('karaoke.favorites.store')),
                    favoriteDestroyBase: @js(url('/karaoke/favorites')),
                    queueStore: @js(route('karaoke.queue.store')),
                    queueUpdateBase: @js(url('/karaoke/queue')),
                    queueDestroyBase: @js(url('/karaoke/queue')),
                },
                csrfToken: @js(csrf_token()),
            };

            window.karaokeRoom = function () {
                return {
                    searchQuery: '',
                    results: [],
                    suggestions: [],
                    selectedVideo: null,
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
                    scoreboardItems: [...(window.karaokeInitialState.scoreboardItems || [])],
                    curatedVideos: [...(window.karaokeInitialState.curatedVideos || [])],
                    trendingVideos: [...(window.karaokeInitialState.trendingVideos || [])],
                    recentVideos: [...(window.karaokeInitialState.recentVideos || [])],
                    favorites: [...(window.karaokeInitialState.favoriteVideos || [])],
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

                    init() {
                        this.loadAudioDevices();
                        const initialQuery = new URLSearchParams(window.location.search).get('q');

                        if (initialQuery) {
                            this.searchQuery = initialQuery;
                            this.$nextTick(() => this.searchSongs());
                        }

                        window.addEventListener('beforeunload', () => this.stopCurrentMicrophone());
                    },

                    playerUrl() {
                        if (!this.selectedVideo) {
                            return '';
                        }

                        return `https://www.youtube.com/embed/${this.selectedVideo.video_id}?autoplay=1&rel=0`;
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

                            if (this.results.length === 0) {
                                this.error = 'No embeddable karaoke videos found. Try another song or artist.';
                            }
                        } catch (error) {
                            this.error = error.message || 'Could not search songs right now.';
                        } finally {
                            this.loading = false;
                        }
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
                        this.searchQuery = video.title;
                        this.lastSearchQuery = video.title;
                        this.suggestions = [];
                        this.suggestionError = '';
                        this.showSuggestions = false;
                        this.playVideo(video);
                    },

                    async queueSuggestion(video) {
                        this.searchQuery = video.title;
                        this.lastSearchQuery = video.title;
                        await this.addToQueue(video);
                    },

                    playVideo(video) {
                        this.selectedVideo = video;
                        this.error = '';
                        this.savePlayHistory(video);

                        this.$nextTick(() => {
                            document.getElementById('player-panel')?.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start',
                            });
                        });
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

                    queuedQueueItems() {
                        return this.sortedQueue().filter((item) => ['queued', 'playing'].includes(item.status));
                    },

                    nextSingerName() {
                        return this.sortedQueue().find((item) => item.status === 'queued')?.singer_name || 'No singer queued';
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
                            this.queueItems = this.queueItems.filter((existing) => existing.id !== queueItem.id);

                            if (queueItem.status === 'done' && queueItem.score !== null) {
                                this.scoreboardItems = [
                                    queueItem,
                                    ...this.scoreboardItems.filter((existing) => existing.id !== queueItem.id),
                                ].slice(0, 6);
                            }

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
                                this.mergeQueueItem(data.queue_item);
                                this.queueStatus = `${video.title} was added for ${singerName}.`;
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
                        this.playVideo(item);
                        await this.updateQueueItem(item, 'playing');
                    },

                    async finishQueueItem(item) {
                        const score = this.scoreDrafts[item.id] ?? 85;
                        await this.updateQueueItem(item, 'done', score);
                        delete this.scoreDrafts[item.id];
                    },

                    async skipQueueItem(item) {
                        await this.updateQueueItem(item, 'skipped');
                    },

                    async removeQueueItem(item) {
                        this.queueStatus = '';

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
    <body class="karaoke-ambient min-h-screen overflow-x-hidden bg-[#08050f] text-white antialiased">
        <div class="pointer-events-none fixed inset-0 overflow-hidden">
            <div class="karaoke-sweep absolute -top-20 left-0 h-[120vh] w-1/2 opacity-70"></div>
            <div class="absolute inset-x-0 bottom-0 h-40 bg-[linear-gradient(180deg,transparent,rgba(8,5,15,0.94))]"></div>
        </div>
        <div class="music-note-field" aria-hidden="true">
            <span class="music-note" style="--note-left: 6%; --note-size: 1.7rem; --note-duration: 20s; --note-delay: -3s; --note-drift: 2.5rem;">&#9834;</span>
            <span class="music-note" style="--note-left: 16%; --note-size: 2.2rem; --note-duration: 25s; --note-delay: -11s; --note-drift: -2rem;">&#9835;</span>
            <span class="music-note" style="--note-left: 31%; --note-size: 1.6rem; --note-duration: 19s; --note-delay: -6s; --note-drift: 3rem;">&#9834;</span>
            <span class="music-note" style="--note-left: 47%; --note-size: 2.6rem; --note-duration: 28s; --note-delay: -16s; --note-drift: -3.5rem;">&#9835;</span>
            <span class="music-note" style="--note-left: 63%; --note-size: 1.9rem; --note-duration: 22s; --note-delay: -9s; --note-drift: 2rem;">&#9834;</span>
            <span class="music-note" style="--note-left: 78%; --note-size: 2.3rem; --note-duration: 24s; --note-delay: -14s; --note-drift: -2.5rem;">&#9835;</span>
            <span class="music-note" style="--note-left: 92%; --note-size: 1.8rem; --note-duration: 21s; --note-delay: -5s; --note-drift: 3rem;">&#9834;</span>
        </div>
        <main
            x-data="karaokeRoom()"
            x-cloak
            class="relative z-10 mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-6 px-4 py-5 sm:px-6 lg:px-8"
        >
            <header class="flex flex-col gap-3 border-b border-[#4c1d95]/30 pb-5 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-semibold text-violet-200">Online karaoke</p>
                    <h1 class="mt-1 text-4xl font-bold leading-tight sm:text-5xl">MyKaraoke</h1>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-sm text-neutral-300">
                    <a href="{{ url('/') }}" class="rounded-lg border border-white/10 px-3 py-2 transition hover:-translate-y-0.5 hover:border-violet-300/60 hover:bg-white/10 hover:text-white">Home</a>
                    <div class="flex items-center gap-3 rounded-lg border border-white/10 px-3 py-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-400 shadow-[0_0_18px_rgba(52,211,153,0.75)]"></span>
                        <span>Ready for requests</span>
                    </div>
                </div>
            </header>

            <section class="relative z-[80] rounded-lg border border-[#4c1d95]/25 bg-[#120b22] p-4 shadow-2xl shadow-[#4c1d95]/10 sm:p-5" data-reveal>
                <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold">Search your song</h2>
                        <p class="text-sm text-violet-100/70">Official YouTube embeds only.</p>
                    </div>
                    <p class="text-sm text-violet-100/70">Region: {{ config('services.youtube.region_code', 'PH') }}</p>
                </div>

                <form class="flex flex-col gap-3 sm:flex-row" x-on:submit.prevent="searchSongs">
                    <div class="relative flex-1" x-on:click.outside="showSuggestions = false">
                        <label class="sr-only" for="song-search">Song or artist</label>
                        <input
                            id="song-search"
                            type="search"
                            x-model="searchQuery"
                            x-on:input="scheduleSuggestions"
                            x-on:focus="searchQuery.trim().length >= 2 && (showSuggestions = true)"
                            maxlength="100"
                            autocomplete="off"
                            placeholder="Enter a song title or artist"
                            class="min-h-14 w-full rounded-lg border border-white/10 bg-[#090612] px-4 text-lg text-white outline-none transition placeholder:text-neutral-500 focus:border-violet-300 focus:ring-2 focus:ring-violet-300/30"
                        >

                        <div
                            class="absolute left-0 right-0 top-[calc(100%+0.5rem)] z-[120] overflow-hidden rounded-lg border border-violet-300/20 bg-[#0e0918] shadow-2xl shadow-black/50"
                            x-show="showSuggestions && (suggestionLoading || suggestions.length > 0 || suggestionError)"
                            x-transition
                        >
                            <div class="border-b border-white/10 px-3 py-2 text-xs font-semibold text-violet-200">
                                Related songs
                            </div>

                            <div class="max-h-96 overflow-y-auto">
                                <template x-for="video in suggestions" x-bind:key="`suggestion-${video.video_id}`">
                                    <div class="flex w-full items-center gap-2 px-3 py-2 transition duration-300 hover:bg-[#4c1d95]/25">
                                        <button
                                            type="button"
                                            class="flex min-w-0 flex-1 items-center gap-3 text-left"
                                            x-on:pointerdown.prevent.stop="chooseSuggestion(video)"
                                        >
                                            <div class="h-14 w-20 shrink-0 overflow-hidden rounded-md bg-neutral-800">
                                                <img
                                                    x-show="video.thumbnail_url"
                                                    x-bind:src="video.thumbnail_url"
                                                    x-bind:alt="video.title"
                                                    class="h-full w-full object-cover"
                                                >
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="line-clamp-1 text-sm font-semibold text-white" x-text="video.title"></p>
                                                <p class="mt-1 truncate text-xs text-neutral-400" x-text="video.channel_title"></p>
                                            </div>
                                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[#4c1d95] text-white">
                                                <span class="sr-only">Play suggestion</span>
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                                    <path d="M6.3 2.841A1.5 1.5 0 0 0 4 4.11v11.78a1.5 1.5 0 0 0 2.3 1.269l9.344-5.89a1.5 1.5 0 0 0 0-2.538L6.3 2.84Z" />
                                                </svg>
                                            </span>
                                        </button>
                                        <div class="flex shrink-0 items-center">
                                            <button
                                                type="button"
                                                class="rounded-lg border border-violet-300/25 bg-white/10 px-3 py-2 text-xs font-semibold text-white transition duration-300 hover:border-violet-200 hover:bg-[#4c1d95]/60 disabled:cursor-not-allowed disabled:opacity-50"
                                                x-on:pointerdown.prevent.stop="queueSuggestion(video)"
                                                x-bind:disabled="queueLoading"
                                            >
                                                <span aria-hidden="true">Queue</span>
                                                <span class="sr-only">Queue suggestion</span>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <div class="px-3 py-4 text-sm text-neutral-400" x-show="suggestionLoading">
                                    Finding related songs...
                                </div>

                                <div class="px-3 py-4 text-sm text-red-100" x-show="suggestionError" x-text="suggestionError"></div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 sm:shrink-0">
                        <button
                            type="submit"
                            class="min-h-14 flex-1 rounded-lg bg-[#4c1d95] px-6 text-base font-semibold text-white shadow-lg shadow-[#4c1d95]/30 transition duration-300 hover:bg-[#5b21b6] focus:outline-none focus:ring-2 focus:ring-violet-200 disabled:cursor-not-allowed disabled:opacity-60 sm:flex-none"
                            x-bind:disabled="loading"
                        >
                            <span x-show="!loading">Search</span>
                            <span x-show="loading">Searching...</span>
                        </button>
                        <button
                            type="button"
                            class="min-h-14 rounded-lg border border-violet-300/25 bg-white/10 px-4 text-sm font-semibold text-white transition duration-300 hover:border-violet-200 hover:bg-[#4c1d95]/60 focus:outline-none focus:ring-2 focus:ring-violet-200 disabled:cursor-not-allowed disabled:opacity-50"
                            x-on:click="addToQueue(selectedVideo)"
                            x-bind:disabled="!selectedVideo || queueLoading"
                        >
                            Queue selected
                        </button>
                    </div>
                </form>
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                <div id="player-panel" class="rounded-lg border border-white/10 bg-[#0e0918] p-4 shadow-xl shadow-black/30 sm:p-5" data-reveal="left">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-2xl font-semibold">Player</h2>
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                class="rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:border-violet-300/60 hover:bg-[#4c1d95]/60 disabled:cursor-not-allowed disabled:opacity-50"
                                x-on:click="fullscreenPlayer"
                                x-bind:disabled="!selectedVideo"
                            >
                                Fullscreen
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:border-violet-300/60 hover:bg-[#4c1d95]/60 disabled:cursor-not-allowed disabled:opacity-50"
                                x-show="selectedVideo"
                                x-on:click="addToQueue(selectedVideo)"
                                x-bind:disabled="queueLoading"
                            >
                                Queue
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:border-violet-300/60 hover:bg-[#4c1d95]/60 disabled:cursor-not-allowed disabled:opacity-50"
                                x-show="selectedVideo"
                                x-on:click="toggleFavorite(selectedVideo)"
                            >
                                <span x-text="selectedVideo && isFavorite(selectedVideo) ? '\u2665' : '\u2661'"></span>
                                <span x-text="selectedVideo && isFavorite(selectedVideo) ? 'Saved' : 'Favorite'"></span>
                            </button>
                            <span class="rounded-full border border-violet-300/40 bg-[#4c1d95]/20 px-3 py-2 text-xs font-semibold text-violet-100">YouTube Embed</span>
                        </div>
                    </div>

                    <div
                        x-ref="playerSurface"
                        class="relative aspect-video overflow-hidden rounded-lg border border-[#4c1d95]/25 bg-black shadow-inner shadow-[#4c1d95]/20"
                    >
                        <template x-if="selectedVideo">
                            <iframe
                                class="h-full w-full"
                                x-bind:src="playerUrl()"
                                x-bind:title="selectedVideo.title"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen
                            ></iframe>
                        </template>

                        <div x-show="!selectedVideo" class="flex h-full items-center justify-center px-6 text-center">
                            <div class="max-w-sm">
                                <p class="text-xl font-semibold text-neutral-200">Choose a karaoke track</p>
                                <p class="mt-2 text-sm text-neutral-500">Search results and trending songs can start the player.</p>
                                <div class="karaoke-equalizer mx-auto mt-6 flex h-12 w-32 items-end justify-center gap-1">
                                    <span class="h-5 w-2 rounded-full bg-violet-400/70"></span>
                                    <span class="h-9 w-2 rounded-full bg-cyan-300/70"></span>
                                    <span class="h-7 w-2 rounded-full bg-fuchsia-300/70"></span>
                                    <span class="h-11 w-2 rounded-full bg-violet-200/70"></span>
                                    <span class="h-6 w-2 rounded-full bg-cyan-200/70"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4" x-show="selectedVideo">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h3 class="text-xl font-semibold" x-text="selectedVideo ? selectedVideo.title : ''"></h3>
                                <p class="mt-1 text-sm text-neutral-400" x-text="selectedVideo ? selectedVideo.channel_title : ''"></p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1 rounded-lg border border-white/10 bg-black/25 p-1">
                                <template x-for="rating in [1, 2, 3, 4, 5]" x-bind:key="`selected-rating-${rating}`">
                                    <button
                                        type="button"
                                        class="grid h-9 w-9 place-items-center rounded-md text-lg transition hover:bg-white/10"
                                        x-bind:class="favoriteRating(selectedVideo) >= rating ? 'text-amber-300' : 'text-neutral-500'"
                                        x-on:click="rateVideo(selectedVideo, rating)"
                                        x-bind:aria-label="`Rate ${rating} out of 5`"
                                    >
                                        &#9733;
                                    </button>
                                </template>
                            </div>
                        </div>
                        <p class="mt-3 text-sm text-violet-100/80" x-show="fullscreenStatus" x-text="fullscreenStatus"></p>
                        <p class="mt-2 text-sm text-violet-100/80" x-show="favoriteStatus" x-text="favoriteStatus"></p>
                    </div>
                </div>

                <aside class="flex flex-col gap-6" data-reveal="right">
                    <section class="rounded-lg border border-white/10 bg-[#0e0918] p-4 shadow-xl shadow-black/30 sm:p-5">
                        <div class="mb-4 flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-2xl font-semibold">Party queue</h2>
                                <p class="mt-1 text-sm text-neutral-400" x-text="`Next: ${nextSingerName()}`"></p>
                            </div>
                            <button
                                type="button"
                                class="rounded-lg border border-violet-300/30 bg-[#4c1d95]/30 px-3 py-2 text-xs font-semibold text-violet-100 transition hover:border-violet-200 hover:bg-[#4c1d95]/60"
                                x-on:click="copyRoomLink"
                                x-text="activeRoom.code"
                            ></button>
                        </div>

                        <label for="singer-name" class="text-sm font-medium text-neutral-300">Singer name</label>
                        <div class="mt-2 flex gap-2">
                            <input
                                id="singer-name"
                                type="text"
                                x-model="singerName"
                                maxlength="80"
                                placeholder="Guest"
                                class="min-w-0 flex-1 rounded-lg border border-white/10 bg-[#090612] px-3 py-3 text-sm text-white outline-none transition placeholder:text-neutral-500 focus:border-violet-300 focus:ring-2 focus:ring-violet-300/30"
                            >
                            <button
                                type="button"
                                class="rounded-lg bg-[#4c1d95] px-3 py-2 text-sm font-semibold text-white shadow-lg shadow-[#4c1d95]/25 transition hover:bg-[#5b21b6] disabled:cursor-not-allowed disabled:opacity-50"
                                x-on:click="addToQueue(selectedVideo)"
                                x-bind:disabled="!selectedVideo || queueLoading"
                            >
                                Add
                            </button>
                        </div>

                        <p class="mt-3 text-sm text-violet-100/80" x-show="queueStatus" x-text="queueStatus"></p>

                        <div class="mt-4 rounded-lg border border-violet-300/20 bg-[#4c1d95]/15 p-3" x-show="playingQueueItem()">
                            <p class="text-xs font-semibold uppercase tracking-wider text-violet-200">Now singing</p>
                            <p class="mt-1 line-clamp-1 font-semibold text-white" x-text="playingQueueItem()?.title"></p>
                            <p class="mt-1 text-sm text-neutral-400" x-text="playingQueueItem()?.singer_name"></p>
                        </div>

                        <div class="mt-4 space-y-3" x-show="queuedQueueItems().length > 0">
                            <template x-for="item in queuedQueueItems()" x-bind:key="`queue-${item.id}`">
                                <div class="rounded-lg border border-white/10 bg-white/[0.04] p-3 transition duration-300 hover:border-violet-300/60 hover:bg-[#4c1d95]/20">
                                    <div class="flex items-start gap-3">
                                        <div class="h-14 w-20 shrink-0 overflow-hidden rounded-md bg-neutral-800">
                                            <img
                                                x-show="item.thumbnail_url"
                                                x-bind:src="item.thumbnail_url"
                                                x-bind:alt="item.title"
                                                class="h-full w-full object-cover"
                                            >
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="rounded-full bg-black/30 px-2 py-1 text-[11px] font-semibold uppercase text-violet-100" x-text="item.status"></span>
                                                <span class="truncate text-xs text-neutral-400" x-text="item.singer_name"></span>
                                            </div>
                                            <p class="mt-1 line-clamp-2 text-sm font-semibold text-white" x-text="item.title"></p>
                                            <p class="mt-1 truncate text-xs text-neutral-500" x-text="item.channel_title"></p>
                                        </div>
                                    </div>

                                    <div class="mt-3 flex flex-wrap items-center gap-2" x-show="item.status === 'queued'">
                                        <button
                                            type="button"
                                            class="rounded-md bg-white/10 px-2.5 py-1.5 text-xs font-semibold text-white transition duration-300 hover:bg-[#4c1d95]/70"
                                            x-on:click="playQueueItem(item)"
                                        >
                                            Play
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-md px-2.5 py-1.5 text-xs font-semibold text-neutral-300 transition duration-300 hover:bg-white/10 hover:text-white"
                                            x-on:click="skipQueueItem(item)"
                                        >
                                            Skip
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-md px-2.5 py-1.5 text-xs font-semibold text-neutral-500 transition duration-300 hover:bg-red-500/10 hover:text-red-100"
                                            x-on:click="removeQueueItem(item)"
                                        >
                                            Remove
                                        </button>
                                    </div>

                                    <div class="mt-3 rounded-lg border border-white/10 bg-black/25 p-2" x-show="item.status === 'playing'">
                                        <label class="text-xs font-medium text-neutral-300" x-bind:for="`score-${item.id}`">Score</label>
                                        <div class="mt-2 flex items-center gap-2">
                                            <input
                                                type="number"
                                                min="0"
                                                max="100"
                                                x-model.number="scoreDrafts[item.id]"
                                                x-bind:id="`score-${item.id}`"
                                                placeholder="85"
                                                class="min-w-0 flex-1 rounded-md border border-white/10 bg-[#090612] px-2 py-2 text-sm text-white outline-none focus:border-violet-300"
                                            >
                                            <button
                                                type="button"
                                                class="rounded-md bg-emerald-500/90 px-3 py-2 text-xs font-semibold text-white transition duration-300 hover:bg-emerald-400"
                                                x-on:click="finishQueueItem(item)"
                                            >
                                                Done
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-4 rounded-lg border border-dashed border-white/10 p-4 text-sm text-neutral-500" x-show="queuedQueueItems().length === 0">
                            Add a song to start the singer rotation.
                        </div>

                        <div class="mt-4" x-show="scoreboardItems.length > 0">
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-white">Top scores</h3>
                                <span class="text-xs text-neutral-500">Latest</span>
                            </div>
                            <div class="space-y-2">
                                <template x-for="item in scoreboardItems" x-bind:key="`scoreboard-${item.id}`">
                                    <div class="flex items-center justify-between gap-3 rounded-md border border-white/10 bg-black/20 px-3 py-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-white" x-text="item.singer_name"></p>
                                            <p class="truncate text-xs text-neutral-500" x-text="item.title"></p>
                                        </div>
                                        <span class="rounded-full bg-emerald-400/15 px-2 py-1 text-sm font-bold text-emerald-200" x-text="`${item.score || 0}`"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-white/10 bg-[#0e0918] p-4 shadow-xl shadow-black/30 sm:p-5">
                        <div class="mb-4">
                            <h2 class="text-2xl font-semibold">Microphone</h2>
                            <p class="mt-1 text-sm text-neutral-400">Input monitor</p>
                        </div>

                        <button
                            type="button"
                            class="w-full rounded-lg bg-[#4c1d95] px-4 py-3 font-semibold text-white shadow-lg shadow-[#4c1d95]/30 transition hover:bg-[#5b21b6] focus:outline-none focus:ring-2 focus:ring-violet-200"
                            x-on:click="enableMicrophone"
                            x-text="microphoneEnabled ? 'Restart Microphone' : 'Enable Microphone'"
                        ></button>

                        <div class="mt-4">
                            <label for="audio-device" class="text-sm font-medium text-neutral-300">Input device</label>
                            <select
                                id="audio-device"
                                x-model="selectedAudioDeviceId"
                                x-on:change="microphoneEnabled && enableMicrophone()"
                                class="mt-2 w-full rounded-lg border border-white/10 bg-[#090612] px-3 py-3 text-sm text-white outline-none focus:border-violet-300 focus:ring-2 focus:ring-violet-300/30"
                            >
                                <option value="">Default input</option>
                                <template x-for="(device, index) in audioDevices" x-bind:key="device.deviceId || index">
                                    <option x-bind:value="device.deviceId" x-text="device.label || `Microphone ${index + 1}`"></option>
                                </template>
                            </select>
                        </div>

                        <div class="mt-4 rounded-lg border border-white/10 bg-black/30 p-3">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="text-neutral-400">Status</span>
                                <span class="text-right text-neutral-100" x-text="microphoneStatus"></span>
                            </div>
                            <div class="mt-4 h-4 overflow-hidden rounded-full bg-neutral-800">
                                <div
                                    class="h-full rounded-full bg-cyan-300 transition-[width] duration-75"
                                    x-bind:style="`width: ${volumeLevel}%`"
                                ></div>
                            </div>
                            <div class="mt-2 text-right text-xs text-neutral-400" x-text="`${volumeLevel}%`"></div>
                        </div>
                    </section>
                </aside>
            </section>

            <section class="grid gap-6 lg:grid-cols-[340px_minmax(0,1fr)]">
                <aside class="flex flex-col gap-6">
                    <section data-reveal x-show="curatedVideos.length > 0">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-xl font-semibold">Curated picks</h2>
                            <span class="text-xs text-violet-200">Featured</span>
                        </div>

                        <div class="space-y-3">
                            <template x-for="video in curatedVideos" x-bind:key="`curated-${video.video_id}`">
                                <button
                                    type="button"
                                    class="flex w-full gap-3 rounded-lg border border-violet-300/20 bg-[#4c1d95]/15 p-2 text-left transition duration-300 hover:-translate-y-0.5 hover:border-violet-200/70 hover:bg-[#4c1d95]/25"
                                    x-on:click="playVideo(video)"
                                >
                                    <div class="h-16 w-24 shrink-0 overflow-hidden rounded-md bg-neutral-800">
                                        <img
                                            x-show="video.thumbnail_url"
                                            x-bind:src="video.thumbnail_url"
                                            x-bind:alt="video.title"
                                            class="h-full w-full object-cover"
                                        >
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="line-clamp-2 text-sm font-semibold text-white" x-text="video.title"></p>
                                        <p class="mt-1 truncate text-xs text-neutral-400" x-text="video.channel_title"></p>
                                        <p class="mt-1 line-clamp-1 text-xs text-violet-200" x-text="video.description || 'Host pick'"></p>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </section>

                    <section data-reveal>
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-xl font-semibold">Favorites</h2>
                            <span class="text-xs text-violet-200" x-text="`${favorites.length} saved`"></span>
                        </div>

                        <div class="space-y-3" x-show="favorites.length > 0">
                            <template x-for="video in favorites" x-bind:key="`favorite-${video.video_id}`">
                                <div class="rounded-lg border border-violet-300/20 bg-[#4c1d95]/15 p-2 transition hover:-translate-y-0.5 hover:border-violet-200/70 hover:bg-[#4c1d95]/25">
                                    <button
                                        type="button"
                                        class="flex w-full gap-3 text-left"
                                        x-on:click="playVideo(video)"
                                    >
                                        <div class="h-16 w-24 shrink-0 overflow-hidden rounded-md bg-neutral-800">
                                            <img
                                                x-show="video.thumbnail_url"
                                                x-bind:src="video.thumbnail_url"
                                                x-bind:alt="video.title"
                                                class="h-full w-full object-cover"
                                            >
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="line-clamp-2 text-sm font-semibold text-white" x-text="video.title"></p>
                                            <p class="mt-1 truncate text-xs text-neutral-400" x-text="video.channel_title"></p>
                                            <div class="mt-1 flex items-center gap-0.5 text-xs">
                                                <template x-for="rating in [1, 2, 3, 4, 5]" x-bind:key="`favorite-rating-${video.video_id}-${rating}`">
                                                    <span x-bind:class="favoriteRating(video) >= rating ? 'text-amber-300' : 'text-neutral-600'">&#9733;</span>
                                                </template>
                                            </div>
                                        </div>
                                    </button>
                                    <div class="mt-2 flex items-center justify-between gap-2 border-t border-white/10 pt-2">
                                        <div class="flex items-center gap-1">
                                            <template x-for="rating in [1, 2, 3, 4, 5]" x-bind:key="`favorite-edit-${video.video_id}-${rating}`">
                                                <button
                                                    type="button"
                                                    class="grid h-7 w-7 place-items-center rounded-md text-sm transition hover:bg-white/10"
                                                    x-bind:class="favoriteRating(video) >= rating ? 'text-amber-300' : 'text-neutral-500'"
                                                    x-on:click="rateVideo(video, rating)"
                                                    x-bind:aria-label="`Rate ${rating} out of 5`"
                                                >
                                                    &#9733;
                                                </button>
                                            </template>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <button
                                                type="button"
                                                class="rounded-md px-2 py-1 text-xs font-semibold text-violet-100 transition duration-300 hover:bg-[#4c1d95]/50"
                                                x-on:click="addToQueue(video)"
                                            >
                                                Queue
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-md px-2 py-1 text-xs font-semibold text-neutral-300 transition duration-300 hover:bg-white/10 hover:text-white"
                                                x-on:click="removeFavorite(video.video_id)"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="rounded-lg border border-dashed border-violet-300/20 bg-[#4c1d95]/10 p-4 text-sm text-violet-100/60" x-show="favorites.length === 0">
                            Favorite songs and ratings will appear here.
                        </div>
                    </section>

                    <section data-reveal>
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-xl font-semibold">Trending songs</h2>
                            <span class="text-xs text-neutral-500">Top plays</span>
                        </div>

                        <div class="space-y-3" x-show="trendingVideos.length > 0">
                            <template x-for="video in trendingVideos" x-bind:key="`trend-${video.video_id}`">
                                <button
                                    type="button"
                                    class="flex w-full gap-3 rounded-lg border border-white/10 bg-white/[0.04] p-2 text-left transition hover:border-violet-300/70 hover:bg-[#4c1d95]/20"
                                    x-on:click="playVideo(video)"
                                >
                                    <div class="h-16 w-24 shrink-0 overflow-hidden rounded-md bg-neutral-800">
                                        <img
                                            x-show="video.thumbnail_url"
                                            x-bind:src="video.thumbnail_url"
                                            x-bind:alt="video.title"
                                            class="h-full w-full object-cover"
                                        >
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="line-clamp-2 text-sm font-semibold text-white" x-text="video.title"></p>
                                        <p class="mt-1 truncate text-xs text-neutral-400" x-text="video.channel_title"></p>
                                        <p class="mt-1 text-xs text-violet-200" x-text="`Played ${video.played_count || 1}x`"></p>
                                    </div>
                                </button>
                            </template>
                        </div>

                        <div class="rounded-lg border border-dashed border-white/10 p-4 text-sm text-neutral-500" x-show="trendingVideos.length === 0">
                            No trending songs yet.
                        </div>
                    </section>

                    <section data-reveal>
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-xl font-semibold">Recently played</h2>
                            <span class="text-xs text-neutral-500">Latest</span>
                        </div>

                        <div class="space-y-3" x-show="recentVideos.length > 0">
                            <template x-for="video in recentVideos" x-bind:key="`recent-${video.video_id}`">
                                <button
                                    type="button"
                                    class="flex w-full gap-3 rounded-lg border border-white/10 bg-white/[0.04] p-2 text-left transition hover:border-cyan-300/70 hover:bg-white/[0.07]"
                                    x-on:click="playVideo(video)"
                                >
                                    <div class="h-14 w-20 shrink-0 overflow-hidden rounded-md bg-neutral-800">
                                        <img
                                            x-show="video.thumbnail_url"
                                            x-bind:src="video.thumbnail_url"
                                            x-bind:alt="video.title"
                                            class="h-full w-full object-cover"
                                        >
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="line-clamp-2 text-sm font-semibold text-white" x-text="video.title"></p>
                                        <p class="mt-1 truncate text-xs text-neutral-400" x-text="video.channel_title"></p>
                                    </div>
                                </button>
                            </template>
                        </div>

                        <div class="rounded-lg border border-dashed border-white/10 p-4 text-sm text-neutral-500" x-show="recentVideos.length === 0">
                            Recently played songs will appear here.
                        </div>
                    </section>
                </aside>

                <section data-reveal>
                    <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-xl font-semibold">Search results</h2>
                        <p class="text-sm text-neutral-400" x-show="results.length > 0" x-text="`${results.length} videos found`"></p>
                    </div>

                    <div
                        class="mb-4 rounded-lg border border-red-400/30 bg-red-500/10 p-3 text-sm text-red-100"
                        x-show="error"
                        x-text="error"
                    ></div>

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" x-show="results.length > 0">
                        <template x-for="video in results" x-bind:key="video.video_id">
                            <article class="group overflow-hidden rounded-lg border border-white/10 bg-[#0e0918] shadow-lg shadow-black/20 transition hover:-translate-y-1 hover:border-violet-300/70 hover:bg-[#4c1d95]/20">
                                <button
                                    type="button"
                                    class="block w-full text-left focus:outline-none focus:ring-2 focus:ring-violet-200"
                                    x-on:click="playVideo(video)"
                                >
                                    <div class="aspect-video bg-neutral-900">
                                        <img
                                            x-show="video.thumbnail_url"
                                            x-bind:src="video.thumbnail_url"
                                            x-bind:alt="video.title"
                                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                        >
                                        <div x-show="!video.thumbnail_url" class="flex h-full items-center justify-center text-sm text-neutral-500">
                                            No thumbnail
                                        </div>
                                    </div>
                                    <div class="p-3">
                                        <h3 class="line-clamp-2 min-h-12 text-base font-semibold text-white" x-text="video.title"></h3>
                                        <p class="mt-2 truncate text-sm text-neutral-400" x-text="video.channel_title"></p>
                                    </div>
                                </button>
                                <div class="flex items-center justify-between gap-2 border-t border-white/10 px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="rounded-md border border-white/10 px-2.5 py-1.5 text-sm font-semibold text-neutral-300 transition duration-300 hover:border-violet-200 hover:bg-white/10 hover:text-white"
                                            x-on:click="addToQueue(video)"
                                            x-bind:disabled="queueLoading"
                                        >
                                            <span x-text="isQueued(video) ? 'Queue again' : 'Queue'"></span>
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-md border border-white/10 px-2.5 py-1.5 text-sm font-semibold transition duration-300 hover:border-violet-200 hover:bg-white/10"
                                            x-bind:class="isFavorite(video) ? 'text-violet-100 bg-[#4c1d95]/40' : 'text-neutral-300'"
                                            x-on:click="toggleFavorite(video)"
                                        >
                                            <span x-text="isFavorite(video) ? '\u2665' : '\u2661'"></span>
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-0.5">
                                        <template x-for="rating in [1, 2, 3, 4, 5]" x-bind:key="`result-rating-${video.video_id}-${rating}`">
                                            <button
                                                type="button"
                                                class="grid h-7 w-7 place-items-center rounded-md text-sm transition hover:bg-white/10"
                                                x-bind:class="favoriteRating(video) >= rating ? 'text-amber-300' : 'text-neutral-500'"
                                                x-on:click="rateVideo(video, rating)"
                                                x-bind:aria-label="`Rate ${rating} out of 5`"
                                            >
                                                &#9733;
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </article>
                        </template>
                    </div>

                    <div class="rounded-lg border border-dashed border-white/10 p-8 text-center text-neutral-500" x-show="!loading && results.length === 0 && !error">
                        Search results will appear here.
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" x-show="loading">
                        <template x-for="index in 6" x-bind:key="index">
                            <div class="overflow-hidden rounded-lg border border-white/10 bg-[#0e0918]">
                                <div class="aspect-video animate-pulse bg-neutral-800"></div>
                                <div class="space-y-3 p-3">
                                    <div class="h-4 w-5/6 animate-pulse rounded bg-neutral-800"></div>
                                    <div class="h-3 w-1/2 animate-pulse rounded bg-neutral-800"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>
            </section>
        </main>
    </body>
</html>
