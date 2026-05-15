<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $artistName }} Karaoke | MyKaraoke</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>[x-cloak]{display:none!important}</style>
    </head>
    <body class="min-h-screen bg-black text-white antialiased">
        <div class="relative min-h-screen">
            <header class="sticky top-0 z-[100] border-b border-white/10 bg-[#111111]/95 backdrop-blur-2xl">
                <nav class="mx-auto grid min-h-20 w-full max-w-[96rem] gap-3 px-4 py-3 md:grid-cols-[13rem_minmax(0,1fr)_auto] md:items-center sm:px-6 lg:px-8">
                    <a href="{{ route('home') }}" class="text-2xl font-black">MyKaraoke</a>
                    <form action="{{ route('songs.index') }}" class="flex items-center gap-3">
                        <a href="{{ route('home') }}" class="hidden h-12 w-12 shrink-0 place-items-center rounded-full bg-white/[0.08] text-violet-200 transition hover:bg-[#4c1d95] hover:text-white md:grid" aria-label="Home">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v5a2 2 0 0 1-2 2h-2.5a.5.5 0 0 1-.5-.5V13a1 1 0 1 0-2 0v4.5a.5.5 0 0 1-.5.5H6a2 2 0 0 1-2-2v-5H3a1 1 0 0 1-.707-1.707l7-7Z" clip-rule="evenodd" /></svg>
                        </a>
                        <input name="q" placeholder="Search songs, artists and playlists" class="min-h-12 flex-1 rounded-full border border-white/10 bg-white/[0.08] px-5 text-sm font-semibold outline-none placeholder:text-neutral-400 focus:border-violet-300 focus:ring-2 focus:ring-violet-300/30">
                        <button class="rounded-full bg-[#4c1d95] px-5 py-3 text-sm font-bold">Search</button>
                    </form>
                    <a href="{{ route('songs.index') }}" class="rounded-full bg-white/10 px-4 py-3 text-center text-xs font-bold transition hover:bg-[#4c1d95]/70">Songs</a>
                </nav>
            </header>

            <main class="mx-auto max-w-[96rem] px-4 py-10 sm:px-6 lg:px-8">
                <section class="grid gap-8 lg:grid-cols-[15rem_minmax(0,1fr)] lg:items-center">
                    <div class="overflow-hidden rounded-lg bg-[#111114] p-4 shadow-2xl shadow-black/40">
                        @if ($coverSong?->thumbnail_url)
                            <img src="{{ $coverSong->thumbnail_url }}" alt="{{ $artistName }}" class="aspect-square w-full rounded-full object-cover">
                        @else
                            <div class="grid aspect-square w-full place-items-center rounded-full bg-[#4c1d95]/30 text-4xl font-black">{{ mb_substr($artistName, 0, 1) }}</div>
                        @endif
                    </div>
                    <div>
                        <h1 class="text-5xl font-black leading-tight">{{ $artistName }} <span class="text-neutral-600">Karaoke</span></h1>
                        <p class="mt-3 text-2xl font-bold text-neutral-500">Artist</p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <button type="button" class="rounded-lg bg-white/10 px-4 py-3 font-bold transition hover:bg-white/15" onclick="navigator.share?.({ title: '{{ $artistName }} Karaoke', url: window.location.href })">Share</button>
                        </div>
                    </div>
                </section>

                <section class="mt-10 rounded-lg bg-[#0d0d0f] p-6">
                    <form class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                        <input name="q" value="{{ $query }}" placeholder="Search {{ $artistName }} songs" class="min-h-14 rounded-full border border-white/10 bg-white/10 px-6 text-lg font-semibold outline-none placeholder:text-neutral-400 focus:border-violet-300 focus:ring-2 focus:ring-violet-300/30">
                        <div class="flex flex-wrap gap-3">
                            @foreach ($featureFilters as $key => $label)
                                <a href="{{ route('artists.show', array_merge(['artistName' => \Illuminate\Support\Str::slug($artistName)], request()->except('page'), ['feature' => $key])) }}" class="rounded-full px-4 py-2 text-sm font-bold transition {{ $activeFeature === $key ? 'bg-white text-black' : 'bg-white/10 text-neutral-300 hover:bg-white/15' }}">{{ $label }}</a>
                            @endforeach
                        </div>
                    </form>

                    @if ($searchError)
                        <div class="mt-5 rounded-lg border border-red-300/20 bg-red-500/10 p-4 text-sm text-red-100">{{ $searchError }}</div>
                    @endif

                    <div class="mt-8 space-y-2">
                        @forelse ($songs as $song)
                            @php($video = $song->toVideoArray())
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
                                    <button type="button" class="block w-full px-4 py-2 text-left transition hover:bg-white/10" x-on:click="$dispatch('mykaraoke:queue', { video: @js($video) }); menuOpen = false">Add to queue</button>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-lg border border-dashed border-white/10 p-8 text-neutral-400">No cached karaoke songs for this artist yet.</div>
                        @endforelse
                    </div>

                    <div class="mt-8">{{ $songs->links() }}</div>
                </section>
            </main>

            @include('karaoke.partials.player', ['activeRoom' => $activeRoom, 'queueItems' => $queueItems])
        </div>
    </body>
</html>
