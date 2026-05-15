<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>MyKaraoke | Songs</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>[x-cloak]{display:none!important}</style>
    </head>
    <body class="min-h-screen bg-black text-white antialiased">
        <div class="relative min-h-screen">
            <header class="sticky top-0 z-[100] border-b border-white/10 bg-[#04150f]/95 backdrop-blur-2xl">
                <nav class="mx-auto grid min-h-20 w-full max-w-[96rem] gap-3 px-4 py-3 md:grid-cols-[13rem_minmax(0,1fr)_auto] md:items-center sm:px-6 lg:px-8">
                    <a href="{{ route('home') }}" class="hidden text-2xl font-black tracking-tight md:block">MyKaraoke</a>
                    <form action="{{ route('songs.index') }}" class="flex min-w-0 flex-wrap items-center gap-3 sm:flex-nowrap">
                        <a href="{{ route('home') }}" class="hidden h-12 w-12 shrink-0 place-items-center rounded-full bg-white/[0.08] text-violet-200 transition hover:bg-[#4c1d95] hover:text-white md:grid" aria-label="Home">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v5a2 2 0 0 1-2 2h-2.5a.5.5 0 0 1-.5-.5V13a1 1 0 1 0-2 0v4.5a.5.5 0 0 1-.5.5H6a2 2 0 0 1-2-2v-5H3a1 1 0 0 1-.707-1.707l7-7Z" clip-rule="evenodd" /></svg>
                        </a>
                        <div class="relative min-w-0 flex-1">
                            <span class="pointer-events-none absolute left-5 top-1/2 -translate-y-1/2 text-neutral-400">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.473 9.765l2.631 2.631a.75.75 0 1 0 1.06-1.06l-2.63-2.632A5.5 5.5 0 0 0 9 3.5Zm-4 5.5a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" /></svg>
                            </span>
                            <input name="q" value="{{ $query }}" placeholder="Search songs, artists and playlists" class="min-h-12 w-full rounded-full border border-white/10 bg-white/[0.08] py-3 pl-12 pr-4 text-sm font-semibold outline-none placeholder:text-neutral-400 focus:border-violet-300 focus:ring-2 focus:ring-violet-300/30">
                        </div>
                        <button class="shrink-0 rounded-full bg-[#4c1d95] px-5 py-3 text-sm font-bold shadow-lg shadow-[#4c1d95]/30 transition hover:bg-[#5b21b6]">Search</button>
                    </form>
                    <a href="{{ route('karaoke.index') }}" class="rounded-full bg-white/10 px-4 py-3 text-center text-xs font-bold transition hover:bg-[#4c1d95]/70">Karaoke room</a>
                </nav>
            </header>

            <main class="mx-auto max-w-[96rem] px-4 py-8 sm:px-6 lg:px-8">
                <div class="mb-6 flex flex-wrap gap-3">
                    <a href="{{ route('songs.index', request()->except('feature')) }}" class="rounded-full px-5 py-3 font-bold {{ $activeFeature === 'all' ? 'bg-white text-black' : 'bg-white/10 text-white' }}">Songs</a>
                    <a href="#artists" class="rounded-full bg-white/10 px-5 py-3 font-bold">Artists</a>
                    <span class="rounded-full bg-white/10 px-5 py-3 font-bold text-neutral-400">Playlists</span>
                </div>

                <section class="grid gap-6 lg:grid-cols-[minmax(17rem,25rem)_minmax(0,1fr)]">
                    <aside class="rounded-lg bg-[#07140e] p-4 sm:p-6">
                        <h2 class="text-2xl font-black">Filter by</h2>

                        <div class="mt-8">
                            <p class="text-lg font-bold">Features</p>
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                @foreach ($featureFilters as $key => $label)
                                    <a href="{{ route('songs.index', array_merge(request()->except('page'), ['feature' => $key])) }}" class="rounded-lg border px-3 py-2 text-sm font-semibold transition {{ $activeFeature === $key ? 'border-violet-300 bg-[#4c1d95] text-white' : 'border-white/10 bg-white/[0.04] text-neutral-300 hover:bg-white/10' }}">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-8">
                            <p class="text-lg font-bold">Genre</p>
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <a href="{{ route('songs.index', array_merge(request()->except('page'), ['genre' => 'all'])) }}" class="rounded-lg border px-3 py-2 text-sm font-semibold transition {{ $activeGenre === 'all' ? 'border-violet-300 bg-[#4c1d95] text-white' : 'border-white/10 bg-white/[0.04] text-neutral-300 hover:bg-white/10' }}">All</a>
                                @foreach ($genreFilters as $key => $label)
                                    <a href="{{ route('songs.index', array_merge(request()->except('page'), ['genre' => $key])) }}" class="rounded-lg border px-3 py-2 text-sm font-semibold transition {{ $activeGenre === $key ? 'border-violet-300 bg-[#4c1d95] text-white' : 'border-white/10 bg-white/[0.04] text-neutral-300 hover:bg-white/10' }}">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </aside>

                    <section class="min-w-0 rounded-lg bg-[#07140e] p-4 sm:p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <h1 class="text-3xl font-black">Songs</h1>
                            <div class="flex items-center gap-3">
                                <select onchange="window.location.href=this.value" class="rounded-lg border border-white/10 bg-white/10 px-4 py-3 font-bold">
                                    <option value="{{ route('songs.index', array_merge(request()->except('page'), ['sort' => 'popular'])) }}" @selected($sort === 'popular')>Most popular</option>
                                    <option value="{{ route('songs.index', array_merge(request()->except('page'), ['sort' => 'latest'])) }}" @selected($sort === 'latest')>Latest</option>
                                    <option value="{{ route('songs.index', array_merge(request()->except('page'), ['sort' => 'played'])) }}" @selected($sort === 'played')>Most played</option>
                                </select>
                            </div>
                        </div>

                        @if ($searchError)
                            <div class="mt-5 rounded-lg border border-red-300/20 bg-red-500/10 p-4 text-sm text-red-100">{{ $searchError }}</div>
                        @endif

                        <div class="mt-8 space-y-2">
                            @forelse ($songs as $song)
                                @php
                                    $video = $song->toVideoArray();
                                    $artistRoute = route('artists.show', ['artistName' => \Illuminate\Support\Str::slug($song->artist_name ?: $song->channel_title ?: 'artist')]);
                                @endphp
                                <article x-data="{ menuOpen: false }" class="group relative grid gap-4 rounded-lg px-3 py-3 transition hover:bg-white/[0.05] md:grid-cols-[minmax(0,1fr)_minmax(16rem,0.7fr)_auto] md:items-center">
                                    <button type="button" class="flex min-w-0 items-center gap-4 text-left" x-on:click="$dispatch('mykaraoke:play', { video: @js($video) })">
                                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-neutral-900">
                                            @if ($song->thumbnail_url)
                                                <img src="{{ $song->thumbnail_url }}" alt="{{ $song->title }}" class="h-full w-full object-cover">
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <h2 class="truncate text-lg font-bold">{{ $song->title }}</h2>
                                            <p class="truncate text-sm text-neutral-400">{{ $song->artist_name ?: $song->channel_title }}</p>
                                        </div>
                                    </button>
                                    <div class="flex flex-wrap gap-2 text-xs font-semibold text-neutral-500">
                                        @foreach (($song->features ?: []) as $feature)
                                            <span class="rounded-full {{ $feature === 'original' ? 'bg-violet-500 text-white' : 'bg-white/10 text-neutral-300' }} px-3 py-1">{{ $featureFilters[$feature] ?? ucfirst($feature) }}</span>
                                        @endforeach
                                        @foreach (array_slice($song->genres ?: [], 0, 2) as $songGenre)
                                            <span>{{ strtoupper($songGenre) }}</span>
                                        @endforeach
                                    </div>
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" class="rounded-full bg-white/10 px-3 py-2 text-xs font-bold transition hover:bg-[#4c1d95]/70" x-on:click.stop="$dispatch('mykaraoke:queue', { video: @js($video) })">Queue</button>
                                        <button type="button" class="grid h-9 w-9 place-items-center rounded-full bg-white/10 transition hover:bg-white/15" x-on:click.stop="menuOpen = !menuOpen" aria-label="More song actions">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path d="M3 10a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm5.5 0a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM15 8.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" /></svg>
                                        </button>
                                    </div>
                                    <div x-show="menuOpen" x-on:click.outside="menuOpen = false" x-transition class="absolute right-3 top-14 z-30 w-48 overflow-hidden rounded-xl border border-white/10 bg-[#151518] py-2 text-sm shadow-2xl shadow-black">
                                        <a href="{{ route('songs.show', $song->video_id) }}" class="block px-4 py-2 transition hover:bg-white/10">View song page</a>
                                        <a href="{{ $artistRoute }}" class="block px-4 py-2 transition hover:bg-white/10">View artist page</a>
                                        <button type="button" class="block w-full px-4 py-2 text-left transition hover:bg-white/10" x-on:click="$dispatch('mykaraoke:queue', { video: @js($video) }); menuOpen = false">Add to queue</button>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-lg border border-dashed border-white/10 p-8 text-neutral-400">Search for a song to grow the karaoke catalog.</div>
                            @endforelse
                        </div>

                        <div class="mt-8">{{ $songs->links() }}</div>
                    </section>
                </section>
            </main>

            @include('karaoke.partials.player', ['activeRoom' => $activeRoom, 'queueItems' => $queueItems])
        </div>
    </body>
</html>
