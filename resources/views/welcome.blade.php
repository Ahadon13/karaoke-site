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

            .stage-scene {
                background:
                    linear-gradient(180deg, rgba(7, 4, 16, 0.14), rgba(7, 4, 16, 0.92)),
                    linear-gradient(105deg, rgba(76, 29, 149, 0.96), rgba(14, 116, 144, 0.48) 48%, rgba(9, 6, 15, 0.98) 76%),
                    #09060f;
            }

            .stage-beam {
                clip-path: polygon(46% 0, 58% 0, 100% 100%, 0 100%);
            }

            .lyric-ticker {
                animation: lyric-scroll 22s linear infinite;
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

                        return `https://www.youtube.com/embed/${this.selectedVideo.video_id}?autoplay=1&rel=0`;
                    },

                    playFeaturedSong(video) {
                        this.selectedVideo = video;
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
    <body class="bg-[#09060f] text-white antialiased">
        <div class="music-note-field" aria-hidden="true">
            <span class="music-note" style="--note-left: 8%; --note-size: 1.7rem; --note-duration: 19s; --note-delay: -2s; --note-drift: 3rem;">&#9834;</span>
            <span class="music-note" style="--note-left: 18%; --note-size: 2.4rem; --note-duration: 24s; --note-delay: -10s; --note-drift: -2rem;">&#9835;</span>
            <span class="music-note" style="--note-left: 29%; --note-size: 1.9rem; --note-duration: 21s; --note-delay: -5s; --note-drift: 2.5rem;">&#9834;</span>
            <span class="music-note" style="--note-left: 43%; --note-size: 2.8rem; --note-duration: 27s; --note-delay: -15s; --note-drift: -3.5rem;">&#9835;</span>
            <span class="music-note" style="--note-left: 57%; --note-size: 1.6rem; --note-duration: 18s; --note-delay: -7s; --note-drift: 2rem;">&#9834;</span>
            <span class="music-note" style="--note-left: 70%; --note-size: 2.2rem; --note-duration: 23s; --note-delay: -12s; --note-drift: -2.5rem;">&#9835;</span>
            <span class="music-note" style="--note-left: 83%; --note-size: 1.8rem; --note-duration: 20s; --note-delay: -4s; --note-drift: 3rem;">&#9834;</span>
            <span class="music-note" style="--note-left: 94%; --note-size: 2.5rem; --note-duration: 26s; --note-delay: -18s; --note-drift: -3rem;">&#9835;</span>
        </div>

        <div x-data="myKaraokeHome()" x-cloak class="relative z-10">
            <header class="fixed inset-x-0 top-0 z-30 border-b border-white/10 bg-[#09060f]/80 backdrop-blur-xl">
                <nav class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <a href="{{ url('/') }}" class="text-base font-semibold transition hover:text-violet-200">
                        MyKaraoke
                    </a>

                    <div class="hidden items-center gap-8 text-sm text-neutral-300 md:flex">
                        <a href="#featured" class="transition hover:text-white">Trending</a>
                        <a href="#easy" class="transition hover:text-white">Easy singing</a>
                        <a href="{{ route('karaoke.index') }}" class="transition hover:text-white">Karaoke room</a>
                    </div>

                    <a
                        href="{{ route('karaoke.index') }}"
                        class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-[#271047] transition hover:-translate-y-0.5 hover:bg-violet-100"
                    >
                        Open room
                    </a>
                </nav>
            </header>

            <main>
                <section class="stage-scene relative flex min-h-[88vh] overflow-hidden border-b border-white/10 pt-16">
                    <div class="pointer-events-none absolute inset-0 opacity-80">
                        <div class="stage-beam absolute -top-8 left-[5%] h-[72%] w-[22rem] rotate-[-18deg] bg-white/10 blur-sm"></div>
                        <div class="stage-beam absolute -top-12 right-[12%] h-[76%] w-[24rem] rotate-[21deg] bg-cyan-300/10 blur-sm"></div>
                        <div class="absolute left-0 right-0 top-20 h-px bg-white/20"></div>
                        <div class="absolute bottom-0 left-0 right-0 h-[34%] bg-[linear-gradient(180deg,rgba(8,8,13,0),rgba(7,7,10,0.94)_48%,#050508)]"></div>
                        <div class="absolute bottom-[16%] left-0 right-0 h-px bg-white/15"></div>
                        <div class="absolute bottom-[9%] left-0 right-0 grid grid-cols-12 gap-2 px-5 opacity-45">
                            @for ($index = 0; $index < 12; $index++)
                                <span class="h-px bg-white/40"></span>
                            @endfor
                        </div>
                    </div>

                    <div class="relative z-10 mx-auto flex w-full max-w-7xl items-center px-4 py-20 sm:px-6 lg:px-8">
                        <div class="max-w-3xl" data-reveal="left">
                            <p class="mb-5 inline-flex rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-violet-100 backdrop-blur">
                                Sing faster with less searching
                            </p>
                            <h1 class="text-5xl font-black leading-tight sm:text-6xl lg:text-7xl">MyKaraoke</h1>
                            <p class="mt-6 max-w-2xl text-lg leading-8 text-violet-50/85 sm:text-xl">
                                Find songs quickly, play music instantly, and open a karaoke version without digging through endless search results.
                            </p>
                            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                                <a
                                    href="#featured"
                                    class="rounded-lg bg-[#4c1d95] px-6 py-4 text-center font-semibold text-white shadow-xl shadow-[#4c1d95]/35 transition hover:-translate-y-0.5 hover:bg-[#5b21b6] focus:outline-none focus:ring-2 focus:ring-violet-200"
                                >
                                    Browse trending songs
                                </a>
                                <a
                                    href="{{ route('karaoke.index') }}"
                                    class="rounded-lg border border-white/15 bg-white/10 px-6 py-4 text-center font-semibold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/15"
                                >
                                    Start karaoke
                                </a>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="featured" class="overflow-hidden bg-[#09060f] px-4 py-16 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-7xl">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between" data-reveal>
                            <div>
                                <p class="text-sm font-semibold text-cyan-300">Ready to play</p>
                                <h2 class="mt-3 text-3xl font-bold sm:text-4xl">Trending songs right now</h2>
                            </div>
                            <a href="{{ route('karaoke.index') }}" class="text-sm font-semibold text-violet-200 transition hover:text-white">Open karaoke room</a>
                        </div>

                        <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_390px]">
                            <div>
                                <div
                                    class="flex gap-5 overflow-x-auto overflow-y-hidden pb-5 [scrollbar-color:#4c1d95_transparent] [scrollbar-width:thin]"
                                    x-show="featuredSongs.length > 0"
                                >
                                    <template x-for="(song, index) in featuredSongs" x-bind:key="song.video_id">
                                        <article
                                            class="group w-[13.5rem] shrink-0 rounded-lg bg-white/[0.045] p-4 transition duration-300 hover:-translate-y-2 hover:bg-white/[0.09] hover:shadow-2xl hover:shadow-[#4c1d95]/25"
                                            data-reveal
                                            x-bind:style="`transition-delay: ${index * 70}ms`"
                                        >
                                            <button type="button" class="block w-full text-left" x-on:click="playFeaturedSong(song)">
                                                <div class="relative aspect-square overflow-hidden rounded-lg bg-[#130827] shadow-xl shadow-black/35">
                                                    <img
                                                        x-show="song.thumbnail_url"
                                                        x-bind:src="song.thumbnail_url"
                                                        x-bind:alt="song.title"
                                                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                                    >
                                                    <div class="absolute inset-0 bg-[linear-gradient(180deg,transparent,rgba(0,0,0,0.78))]"></div>
                                                    <span class="absolute bottom-4 right-4 grid h-12 w-12 translate-y-3 place-items-center rounded-full bg-[#4c1d95] text-white opacity-0 shadow-lg shadow-black/30 transition group-hover:translate-y-0 group-hover:opacity-100">
                                                        <span class="sr-only">Play</span>
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6" aria-hidden="true">
                                                            <path d="M6.3 2.841A1.5 1.5 0 0 0 4 4.11v11.78a1.5 1.5 0 0 0 2.3 1.269l9.344-5.89a1.5 1.5 0 0 0 0-2.538L6.3 2.84Z" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <h3 class="mt-4 line-clamp-2 min-h-12 text-base font-bold" x-text="song.title"></h3>
                                                <p class="mt-1 truncate text-sm text-neutral-400" x-text="song.channel_title"></p>
                                            </button>
                                            <a
                                                class="mt-3 inline-flex rounded-md border border-white/10 px-3 py-2 text-xs font-semibold text-violet-100 transition hover:border-violet-200 hover:bg-[#4c1d95]/40"
                                                x-bind:href="karaokeSearchUrl(song)"
                                            >
                                                Find karaoke version
                                            </a>
                                        </article>
                                    </template>
                                </div>

                                <div
                                    class="rounded-lg border border-dashed border-violet-300/25 bg-[#4c1d95]/10 p-6 text-sm text-violet-100/75"
                                    x-show="featuredSongs.length === 0"
                                >
                                    <p class="font-semibold">Trending songs are not available yet.</p>
                                    <p class="mt-2" x-text="featuredError || 'Open the karaoke room and search for any song you want to sing.'"></p>
                                </div>
                            </div>

                            <aside id="featured-player" class="rounded-lg border border-white/10 bg-white/[0.045] p-4 shadow-2xl shadow-black/30" data-reveal="right">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-violet-200">Now playing</p>
                                        <h3 class="mt-1 text-xl font-bold" x-text="selectedVideo ? selectedVideo.title : 'Select a trending song'"></h3>
                                    </div>
                                    <span class="rounded-full bg-[#4c1d95]/70 px-3 py-1 text-xs font-semibold">Music mode</span>
                                </div>

                                <div class="aspect-video overflow-hidden rounded-lg border border-white/10 bg-black">
                                    <template x-if="selectedVideo">
                                        <iframe
                                            class="h-full w-full"
                                            x-bind:src="playerUrl()"
                                            x-bind:title="selectedVideo.title"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                            allowfullscreen
                                        ></iframe>
                                    </template>
                                    <div x-show="!selectedVideo" class="flex h-full items-center justify-center px-5 text-center text-sm text-neutral-400">
                                        Choose a trending song to play here.
                                    </div>
                                </div>

                                <p class="mt-3 truncate text-sm text-neutral-400" x-show="selectedVideo" x-text="selectedVideo ? selectedVideo.channel_title : ''"></p>
                                <a
                                    x-show="selectedVideo"
                                    x-bind:href="selectedVideo ? karaokeSearchUrl(selectedVideo) : '#'"
                                    class="mt-4 inline-flex w-full justify-center rounded-lg bg-[#4c1d95] px-4 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-[#5b21b6]"
                                >
                                    Search karaoke version
                                </a>
                            </aside>
                        </div>

                        <div class="mt-4 overflow-hidden rounded-lg border border-white/10 bg-white/[0.04] py-3" data-reveal>
                            <div class="lyric-ticker flex w-[200%] gap-8 whitespace-nowrap text-sm font-semibold text-violet-100/70">
                                <span>Trending songs</span>
                                <span>Play on the homepage</span>
                                <span>Jump into karaoke</span>
                                <span>Save favorites</span>
                                <span>Rate your go-to songs</span>
                                <span>Trending songs</span>
                                <span>Play on the homepage</span>
                                <span>Jump into karaoke</span>
                                <span>Save favorites</span>
                                <span>Rate your go-to songs</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="easy" class="bg-[#0d0817] px-4 py-16 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-7xl">
                        <div class="max-w-2xl" data-reveal>
                            <p class="text-sm font-semibold text-cyan-300">No hassle</p>
                            <h2 class="mt-3 text-3xl font-bold sm:text-4xl">Everything is built around getting to the song faster.</h2>
                        </div>

                        <div class="mt-10 grid gap-4 md:grid-cols-3">
                            <article class="rounded-lg border border-white/10 bg-white/[0.04] p-6 transition hover:-translate-y-1 hover:border-violet-300/50 hover:bg-white/[0.07]" data-reveal>
                                <div class="mb-5 h-1.5 w-20 rounded-full bg-[#4c1d95]"></div>
                                <h3 class="text-xl font-semibold">Pick a song fast</h3>
                                <p class="mt-3 text-sm leading-6 text-neutral-400">Use trending songs as a shortcut or search directly when someone already knows what to sing.</p>
                            </article>

                            <article class="rounded-lg border border-white/10 bg-white/[0.04] p-6 transition hover:-translate-y-1 hover:border-cyan-300/50 hover:bg-white/[0.07]" data-reveal>
                                <div class="mb-5 h-1.5 w-20 rounded-full bg-cyan-400"></div>
                                <h3 class="text-xl font-semibold">Listen first</h3>
                                <p class="mt-3 text-sm leading-6 text-neutral-400">Preview a song on the homepage before opening the karaoke version for the room.</p>
                            </article>

                            <article class="rounded-lg border border-white/10 bg-white/[0.04] p-6 transition hover:-translate-y-1 hover:border-fuchsia-300/50 hover:bg-white/[0.07]" data-reveal>
                                <div class="mb-5 h-1.5 w-20 rounded-full bg-fuchsia-400"></div>
                                <h3 class="text-xl font-semibold">Sing with less tapping</h3>
                                <p class="mt-3 text-sm leading-6 text-neutral-400">Open the karaoke room with the song search already filled, then play and save your favorites.</p>
                            </article>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
