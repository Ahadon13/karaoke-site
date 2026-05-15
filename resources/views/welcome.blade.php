<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>MyKaraoke</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            [x-cloak] {
                display: none !important;
            }

            .home-shell {
                background:
                    radial-gradient(circle at 14% 8%, rgba(76, 29, 149, 0.42), transparent 28rem),
                    radial-gradient(circle at 88% 18%, rgba(34, 211, 238, 0.16), transparent 22rem),
                    #030305;
            }

            .feature-glow {
                background:
                    radial-gradient(circle at 20% 20%, rgba(76, 29, 149, 0.34), transparent 16rem),
                    radial-gradient(circle at 84% 64%, rgba(34, 211, 238, 0.14), transparent 14rem),
                    rgba(255, 255, 255, 0.035);
            }

            .lyric-ticker {
                animation: lyric-scroll 24s linear infinite;
            }

            @keyframes lyric-scroll {
                from {
                    transform: translateX(0);
                }

                to {
                    transform: translateX(-50%);
                }
            }
        </style>

        <script>
            window.myKaraokeHomeState = {
                featuredSongs: @js($featuredSongs),
                featuredError: @js($featuredError),
                karaokeUrl: @js(route('karaoke.index')),
            };

            window.myKaraokeHome = function () {
                return {
                    featuredSongs: [...(window.myKaraokeHomeState.featuredSongs || [])],
                    featuredError: window.myKaraokeHomeState.featuredError,
                    selectedVideo: null,
                    karaokeUrl: window.myKaraokeHomeState.karaokeUrl,

                    playerUrl() {
                        if (!this.selectedVideo) {
                            return '';
                        }

                        const params = new URLSearchParams({
                            autoplay: '1',
                            rel: '0',
                            playsinline: '1',
                            origin: window.location.origin,
                        });

                        return `https://www.youtube.com/embed/${this.selectedVideo.video_id}?${params.toString()}`;
                    },

                    playFeaturedSong(video) {
                        this.selectedVideo = video;
                        window.dispatchEvent(new CustomEvent('mykaraoke:play', {
                            detail: { video },
                        }));
                        this.$nextTick(() => {
                            document.getElementById('featured-player')?.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center',
                            });
                        });
                    },

                    karaokeSearchUrl(video) {
                        return `${this.karaokeUrl}?q=${encodeURIComponent(`${video.title} ${video.channel_title}`)}`;
                    },
                };
            };
        </script>
    </head>
    <body class="home-shell min-h-screen overflow-x-hidden text-white antialiased">
        <div class="music-note-field" aria-hidden="true">
            <span class="music-note" style="--note-left: 8%; --note-size: 1.7rem; --note-duration: 19s; --note-delay: -2s; --note-drift: 3rem;">&#9834;</span>
            <span class="music-note" style="--note-left: 18%; --note-size: 2.4rem; --note-duration: 24s; --note-delay: -10s; --note-drift: -2rem;">&#9835;</span>
            <span class="music-note" style="--note-left: 31%; --note-size: 1.9rem; --note-duration: 21s; --note-delay: -5s; --note-drift: 2.5rem;">&#9834;</span>
            <span class="music-note" style="--note-left: 47%; --note-size: 2.8rem; --note-duration: 27s; --note-delay: -15s; --note-drift: -3.5rem;">&#9835;</span>
            <span class="music-note" style="--note-left: 63%; --note-size: 1.6rem; --note-duration: 18s; --note-delay: -7s; --note-drift: 2rem;">&#9834;</span>
            <span class="music-note" style="--note-left: 78%; --note-size: 2.2rem; --note-duration: 23s; --note-delay: -12s; --note-drift: -2.5rem;">&#9835;</span>
            <span class="music-note" style="--note-left: 92%; --note-size: 1.8rem; --note-duration: 20s; --note-delay: -4s; --note-drift: 3rem;">&#9834;</span>
        </div>

        <div x-data="myKaraokeHome()" x-cloak class="relative z-10">
            <header class="fixed inset-x-0 top-0 z-40 border-b border-white/10 bg-black/70 backdrop-blur-xl">
                <nav class="mx-auto flex h-16 w-full max-w-7xl items-center justify-end px-4 md:justify-between sm:px-6 lg:px-8">
                    <a href="{{ url('/') }}" class="hidden text-lg font-black tracking-tight text-white transition hover:text-violet-200 md:block">
                        MyKaraoke
                    </a>

                    <div class="hidden items-center gap-8 text-xs font-semibold text-neutral-300 md:flex">
                        <a href="#featured" class="transition hover:text-white">Songs</a>
                        <a href="#anywhere" class="transition hover:text-white">How it works</a>
                        <a href="#features" class="transition hover:text-white">Features</a>
                    </div>

                    <a
                        href="{{ route('karaoke.index') }}"
                        class="rounded-md bg-[#4c1d95] px-4 py-2 text-sm font-bold text-white shadow-lg shadow-[#4c1d95]/35 transition hover:-translate-y-0.5 hover:bg-[#5b21b6]"
                    >
                        Open room
                    </a>
                </nav>
            </header>

            <main>
                <section class="relative min-h-[92vh] overflow-hidden border-b border-white/10 pt-16">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_18%,rgba(76,29,149,0.36),transparent_26rem)]"></div>
                    <div class="absolute -left-24 top-12 h-[28rem] w-[34rem] rounded-[45%] bg-white/[0.06] blur-2xl"></div>

                    <div class="relative mx-auto grid min-h-[calc(92vh-4rem)] w-full max-w-7xl items-center gap-10 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-[minmax(0,1fr)_30rem] lg:px-8">
                        <div data-reveal="left">
                            <p class="mb-5 inline-flex rounded-full border border-white/10 bg-white/[0.06] px-4 py-2 text-sm font-semibold text-violet-100">
                                Karaoke without the search hassle
                            </p>
                            <h1 class="max-w-3xl text-4xl font-black leading-tight sm:text-6xl lg:text-7xl">
                                Sing your favorite karaoke songs on any device
                            </h1>
                            <p class="mt-6 max-w-2xl text-base leading-8 text-neutral-300 sm:text-lg">
                                MyKaraoke helps you find songs, preview music, queue singers, and jump into an official YouTube karaoke player with less tapping.
                            </p>
                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <a
                                    href="{{ route('karaoke.index') }}"
                                    class="rounded-md bg-[#4c1d95] px-6 py-4 text-center text-sm font-bold text-white shadow-xl shadow-[#4c1d95]/35 transition hover:-translate-y-0.5 hover:bg-[#5b21b6]"
                                >
                                    Start singing
                                </a>
                                <a
                                    href="#featured"
                                    class="rounded-md border border-white/15 bg-white/[0.06] px-6 py-4 text-center text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-white/10"
                                >
                                    Browse songs
                                </a>
                            </div>
                        </div>

                        <div class="relative mx-auto w-full max-w-md lg:max-w-none" data-reveal="right" aria-label="MyKaraoke preview video">
                            <div class="absolute -inset-4 rounded-[2rem] bg-[#4c1d95]/30 blur-3xl"></div>
                            <div class="relative overflow-hidden rounded-2xl border border-white/10 bg-black shadow-2xl shadow-[#4c1d95]/30">
                                <video
                                    class="aspect-[4/5] w-full object-cover sm:aspect-[5/6] lg:aspect-[4/5]"
                                    src="{{ asset('videos/yummy.mp4') }}"
                                    autoplay
                                    muted
                                    loop
                                    playsinline
                                    preload="metadata"
                                    aria-hidden="true"
                                ></video>
                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/55 via-transparent to-[#4c1d95]/10"></div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="featured" class="bg-[#07070a] px-4 py-16 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-7xl">
                        <div class="text-center" data-reveal>
                            <p class="text-sm font-bold text-violet-300">Give it a spin</p>
                            <h2 class="mt-3 text-3xl font-black sm:text-5xl">Search, select, and play</h2>
                            <div class="mx-auto mt-6 flex max-w-xl items-center gap-3 rounded-full border border-white/10 bg-white/[0.06] px-5 py-3 text-left text-sm text-neutral-400">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-violet-200" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.473 9.765l2.631 2.631a.75.75 0 1 0 1.06-1.06l-2.63-2.632A5.5 5.5 0 0 0 9 3.5Zm-4 5.5a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                                </svg>
                                <span>Pick a trending song below or open the karaoke room to search.</span>
                            </div>
                        </div>

                        <div class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_28rem]">
                            <div class="min-w-0">
                                <div
                                    class="flex gap-5 overflow-x-auto overflow-y-hidden py-5 [scrollbar-color:#4c1d95_transparent] [scrollbar-width:thin]"
                                    x-show="featuredSongs.length > 0"
                                >
                                    <template x-for="(song, index) in featuredSongs" x-bind:key="song.video_id">
                                        <article
                                            class="group w-[13.5rem] shrink-0 rounded-md border border-white/10 bg-white/[0.05] p-3 transition duration-300 hover:-translate-y-2 hover:border-violet-300/40 hover:bg-white/[0.09] hover:shadow-2xl hover:shadow-[#4c1d95]/25"
                                            data-reveal
                                            x-bind:style="`transition-delay: ${index * 65}ms`"
                                        >
                                            <button type="button" class="block w-full text-left" x-on:click="playFeaturedSong(song)">
                                                <div class="relative aspect-square overflow-hidden rounded-md bg-[#130827] shadow-xl shadow-black/35">
                                                    <img
                                                        x-show="song.thumbnail_url"
                                                        x-bind:src="song.thumbnail_url"
                                                        x-bind:alt="song.title"
                                                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                                    >
                                                    <div class="absolute inset-0 bg-[linear-gradient(180deg,transparent,rgba(0,0,0,0.78))]"></div>
                                                    <span class="absolute bottom-3 right-3 grid h-11 w-11 translate-y-3 place-items-center rounded-full bg-[#4c1d95] text-white opacity-0 shadow-lg shadow-black/30 transition group-hover:translate-y-0 group-hover:opacity-100">
                                                        <span class="sr-only">Play on homepage</span>
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                                            <path d="M6.3 2.841A1.5 1.5 0 0 0 4 4.11v11.78a1.5 1.5 0 0 0 2.3 1.269l9.344-5.89a1.5 1.5 0 0 0 0-2.538L6.3 2.84Z" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <h3 class="mt-4 line-clamp-2 min-h-12 text-base font-bold" x-text="song.title"></h3>
                                                <p class="mt-1 truncate text-sm text-neutral-400" x-text="song.channel_title"></p>
                                            </button>
                                            <a
                                                class="mt-3 inline-flex rounded-md border border-white/10 px-3 py-2 text-xs font-bold text-violet-100 transition hover:border-violet-200 hover:bg-[#4c1d95]/40"
                                                x-bind:href="karaokeSearchUrl(song)"
                                            >
                                                Find karaoke
                                            </a>
                                        </article>
                                    </template>
                                </div>

                                <div
                                    class="rounded-md border border-dashed border-violet-300/25 bg-[#4c1d95]/10 p-6 text-sm text-violet-100/75"
                                    x-show="featuredSongs.length === 0"
                                >
                                    <p class="font-bold">Trending songs are not available yet.</p>
                                    <p class="mt-2" x-text="featuredError || 'Open the karaoke room and search for any song you want to sing.'"></p>
                                </div>

                                <a
                                    href="{{ route('karaoke.index') }}"
                                    class="mt-6 inline-flex rounded-md bg-[#4c1d95] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-[#4c1d95]/30 transition hover:-translate-y-0.5 hover:bg-[#5b21b6]"
                                >
                                    Explore the catalog
                                </a>
                            </div>

                            <aside id="featured-player" class="rounded-md border border-white/10 bg-[#111114] p-4 shadow-2xl shadow-black/30" data-reveal="right">
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-violet-200">Stage player</p>
                                        <h3 class="mt-1 line-clamp-2 text-xl font-black" x-text="selectedVideo ? selectedVideo.title : 'Choose a song to open the player'"></h3>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-[#4c1d95]/70 px-3 py-1 text-xs font-bold">Overlay</span>
                                </div>

                                <div class="aspect-video overflow-hidden rounded-md border border-white/10 bg-black">
                                    <div class="flex h-full items-center justify-center px-5 text-center text-sm text-neutral-400">
                                        <div>
                                            <div class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-full bg-[#4c1d95] text-white">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6" aria-hidden="true">
                                                    <path d="M6.3 2.841A1.5 1.5 0 0 0 4 4.11v11.78a1.5 1.5 0 0 0 2.3 1.269l9.344-5.89a1.5 1.5 0 0 0 0-2.538L6.3 2.84Z" />
                                                </svg>
                                            </div>
                                            <p x-text="selectedVideo ? 'The song is playing in the stage overlay.' : 'Select a song card to open the stage player.'"></p>
                                        </div>
                                    </div>
                                </div>

                                <p class="mt-3 truncate text-sm text-neutral-400" x-show="selectedVideo" x-text="selectedVideo ? selectedVideo.channel_title : ''"></p>
                                <button
                                    type="button"
                                    x-show="selectedVideo"
                                    x-on:click="selectedVideo && playFeaturedSong(selectedVideo)"
                                    class="mt-4 inline-flex w-full justify-center rounded-md bg-[#4c1d95] px-4 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-[#5b21b6]"
                                >
                                    Reopen player
                                </button>
                            </aside>
                        </div>
                    </div>
                </section>

                <section id="anywhere" class="border-y border-white/10 bg-black px-4 py-16 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-7xl">
                        <div data-reveal>
                            <p class="text-sm font-bold text-violet-300">Sing karaoke anywhere</p>
                            <h2 class="mt-3 max-w-2xl text-3xl font-black sm:text-5xl">From quick solo songs to a full room queue.</h2>
                        </div>

                        <div class="mt-10 grid gap-6 md:grid-cols-3">
                            <article class="rounded-md border border-white/10 bg-white/[0.04] p-6 transition hover:-translate-y-1 hover:border-violet-300/40 hover:bg-white/[0.07]" data-reveal>
                                <div class="mb-5 grid h-11 w-11 place-items-center rounded-md bg-[#4c1d95]/30 text-violet-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                        <path d="M7 2.75A.75.75 0 0 1 7.75 2h4.5a.75.75 0 0 1 .75.75v14.5a.75.75 0 0 1-.75.75h-4.5A.75.75 0 0 1 7 17.25V2.75Z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold">Sing on any device</h3>
                                <p class="mt-3 text-sm leading-6 text-neutral-400">Search, play, and queue from a responsive web page built for desktop and mobile.</p>
                            </article>

                            <article class="rounded-md border border-white/10 bg-white/[0.04] p-6 transition hover:-translate-y-1 hover:border-cyan-300/40 hover:bg-white/[0.07]" data-reveal>
                                <div class="mb-5 grid h-11 w-11 place-items-center rounded-md bg-cyan-400/15 text-cyan-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                        <path d="M18 3.75a.75.75 0 0 0-.9-.735l-10 2A.75.75 0 0 0 6.5 5.75v7.063A3.5 3.5 0 1 0 8 15.5V8.365l8.5-1.7v4.148A3.5 3.5 0 1 0 18 13.5V3.75Z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold">Find songs faster</h3>
                                <p class="mt-3 text-sm leading-6 text-neutral-400">Preview trending music, then launch a karaoke search with one click.</p>
                            </article>

                            <article class="rounded-md border border-white/10 bg-white/[0.04] p-6 transition hover:-translate-y-1 hover:border-fuchsia-300/40 hover:bg-white/[0.07]" data-reveal>
                                <div class="mb-5 grid h-11 w-11 place-items-center rounded-md bg-fuchsia-400/15 text-fuchsia-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                                        <path d="M10 3a5.5 5.5 0 0 0-5.5 5.5v1.275A2.5 2.5 0 0 0 5.25 14.5h.25A1.5 1.5 0 0 0 7 13V9.5A1.5 1.5 0 0 0 5.5 8h-.25a4.75 4.75 0 0 1 9.5 0h-.25A1.5 1.5 0 0 0 13 9.5V13a1.5 1.5 0 0 0 1.5 1.5h.25A2.5 2.5 0 0 0 15.5 9.775V8.5A5.5 5.5 0 0 0 10 3Z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold">Run the rotation</h3>
                                <p class="mt-3 text-sm leading-6 text-neutral-400">Queue singers, move to the next song, and keep the player focused on the stage.</p>
                            </article>
                        </div>
                    </div>
                </section>

                <section id="features" class="bg-[#050507] px-4 py-16 sm:px-6 lg:px-8">
                    <div class="mx-auto grid max-w-7xl gap-12 lg:grid-cols-2">
                        <div class="feature-glow rounded-md border border-white/10 p-6 sm:p-8" data-reveal="left">
                            <p class="text-sm font-bold text-violet-300">Original karaoke room</p>
                            <h2 class="mt-3 text-3xl font-black sm:text-4xl">Search once, then keep the party moving.</h2>
                            <p class="mt-4 text-sm leading-7 text-neutral-400">
                                The karaoke room keeps search, microphone input, YouTube playback, favorites, and singer queue controls in one place.
                            </p>
                            <a
                                href="{{ route('karaoke.index') }}"
                                class="mt-6 inline-flex rounded-md bg-[#4c1d95] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-[#4c1d95]/30 transition hover:-translate-y-0.5 hover:bg-[#5b21b6]"
                            >
                                Open karaoke room
                            </a>
                        </div>

                        <div class="grid gap-6" data-reveal="right">
                            <article class="rounded-md border border-white/10 bg-white/[0.04] p-6">
                                <p class="text-sm font-bold text-cyan-300">Music first</p>
                                <h3 class="mt-2 text-2xl font-black">Play trending tracks before karaoke mode.</h3>
                                <p class="mt-3 text-sm leading-6 text-neutral-400">Use the homepage player to listen first, then jump into a karaoke search when the room is ready.</p>
                            </article>

                            <article class="rounded-md border border-white/10 bg-white/[0.04] p-6">
                                <p class="text-sm font-bold text-violet-300">Queue friendly</p>
                                <h3 class="mt-2 text-2xl font-black">Built for shared singing sessions.</h3>
                                <p class="mt-3 text-sm leading-6 text-neutral-400">Add songs from suggestions, favorites, and trending lists, then move through the queue with the player controls.</p>
                            </article>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden border-t border-white/10 bg-black py-4">
                    <div class="lyric-ticker flex w-[200%] gap-8 whitespace-nowrap text-sm font-bold text-violet-100/70">
                        <span>Trending songs</span>
                        <span>Official YouTube player</span>
                        <span>Karaoke queue</span>
                        <span>Favorites</span>
                        <span>Microphone meter</span>
                        <span>Trending songs</span>
                        <span>Official YouTube player</span>
                        <span>Karaoke queue</span>
                        <span>Favorites</span>
                        <span>Microphone meter</span>
                    </div>
                </section>
            </main>

            @include('karaoke.partials.player', ['activeRoom' => $activeRoom, 'queueItems' => $queueItems])
        </div>
    </body>
</html>
