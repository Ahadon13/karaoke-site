<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $song->title }} | MyKaraoke</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>[x-cloak]{display:none!important}</style>
    </head>
    <body class="min-h-screen bg-black text-white antialiased">
        @php
            $video = $song->toVideoArray();
            $artistRoute = route('artists.show', ['artistName' => \Illuminate\Support\Str::slug($song->artist_name ?: $song->channel_title ?: 'artist')]);
        @endphp
        <div class="relative min-h-screen">
            <header class="sticky top-0 z-[100] border-b border-white/10 bg-[#130d0d]/95 backdrop-blur-2xl">
                <nav class="mx-auto grid min-h-20 w-full max-w-[96rem] gap-3 px-4 py-3 md:grid-cols-[13rem_minmax(0,1fr)_auto] md:items-center sm:px-6 lg:px-8">
                    <a href="{{ route('home') }}" class="hidden text-2xl font-black md:block">MyKaraoke</a>
                    <form action="{{ route('songs.index') }}" class="flex min-w-0 flex-wrap items-center gap-3 sm:flex-nowrap">
                        <a href="{{ route('home') }}" class="hidden h-12 w-12 shrink-0 place-items-center rounded-full bg-white/[0.08] text-violet-200 transition hover:bg-[#4c1d95] hover:text-white md:grid" aria-label="Home">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v5a2 2 0 0 1-2 2h-2.5a.5.5 0 0 1-.5-.5V13a1 1 0 1 0-2 0v4.5a.5.5 0 0 1-.5.5H6a2 2 0 0 1-2-2v-5H3a1 1 0 0 1-.707-1.707l7-7Z" clip-rule="evenodd" /></svg>
                        </a>
                        <input name="q" placeholder="Search songs, artists and playlists" class="min-h-12 min-w-0 flex-1 rounded-full border border-white/10 bg-white/[0.08] px-5 text-sm font-semibold outline-none placeholder:text-neutral-400 focus:border-violet-300 focus:ring-2 focus:ring-violet-300/30">
                        <button class="shrink-0 rounded-full bg-[#4c1d95] px-5 py-3 text-sm font-bold">Search</button>
                    </form>
                    <a href="{{ route('songs.index') }}" class="rounded-full bg-white/10 px-4 py-3 text-center text-xs font-bold transition hover:bg-[#4c1d95]/70">Songs</a>
                </nav>
            </header>

            <main class="mx-auto max-w-[96rem] px-4 py-8 sm:px-6 lg:px-8">
                <a href="{{ url()->previous() }}" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-4 py-3 text-sm font-bold text-neutral-200 transition hover:bg-white/15">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.56l4.22 4.22a.75.75 0 1 1-1.06 1.06l-5.5-5.5a.75.75 0 0 1 0-1.06l5.5-5.5a.75.75 0 0 1 1.06 1.06L5.56 9.25h10.69A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>
                    Go back
                </a>

                <section class="mt-8 grid gap-8 lg:grid-cols-[18rem_minmax(0,1fr)_auto] lg:items-center">
                    <div class="overflow-hidden rounded-lg bg-[#111114] p-4 shadow-2xl shadow-black/40">
                        @if ($song->thumbnail_url)
                            <img src="{{ $song->thumbnail_url }}" alt="{{ $song->title }}" class="aspect-square w-full rounded-md object-cover">
                        @endif
                    </div>
                    <div>
                        <h1 class="text-3xl font-black leading-tight sm:text-5xl">{{ $song->title }} <span class="text-neutral-600">Karaoke</span></h1>
                        <a href="{{ $artistRoute }}" class="mt-4 block text-xl font-bold text-neutral-400 transition hover:text-violet-200 sm:text-2xl">{{ $song->artist_name ?: $song->channel_title }}</a>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-[#4c1d95] px-6 py-3 font-black text-white shadow-lg shadow-[#4c1d95]/30 transition hover:bg-[#5b21b6]" x-data x-on:click="$dispatch('mykaraoke:play', { video: @js($video) })">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path d="M6.3 2.841A1.5 1.5 0 0 0 4 4.11v11.78a1.5 1.5 0 0 0 2.3 1.269l9.344-5.89a1.5 1.5 0 0 0 0-2.538L6.3 2.84Z" /></svg>
                                Sing
                            </button>
                            <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-6 py-3 font-black text-white transition hover:bg-white/15" x-data x-on:click="$dispatch('mykaraoke:queue', { video: @js($video) })">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M2 4.75A.75.75 0 0 1 2.75 4h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 4.75ZM2 10a.75.75 0 0 1 .75-.75h9.5a.75.75 0 0 1 0 1.5h-9.5A.75.75 0 0 1 2 10Zm.75 4.5a.75.75 0 0 0 0 1.5h14.5a.75.75 0 0 0 0-1.5H2.75Z" clip-rule="evenodd" /></svg>
                                Queue
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs font-semibold text-neutral-500 lg:max-w-sm">
                        @foreach (($song->features ?: []) as $feature)
                            <span class="rounded-full {{ $feature === 'original' ? 'bg-violet-500 text-white' : 'bg-white/10 text-neutral-300' }} px-3 py-1">{{ ucfirst($feature) }}</span>
                        @endforeach
                    </div>
                </section>

                <section class="mt-16">
                    <h2 class="text-2xl font-black">More from {{ $song->artist_name ?: $song->channel_title }}</h2>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                        @foreach ($moreFromArtist as $related)
                            @php($relatedVideo = $related->toVideoArray())
                            <button type="button" class="group rounded-lg bg-[#111114] p-3 text-left transition hover:-translate-y-2 hover:bg-white/[0.08]" x-data x-on:click="$dispatch('mykaraoke:play', { video: @js($relatedVideo) })">
                                <img src="{{ $related->thumbnail_url }}" alt="{{ $related->title }}" class="aspect-square w-full rounded-md object-cover transition duration-500 group-hover:scale-[1.02]">
                                <p class="mt-4 line-clamp-2 font-bold">{{ $related->title }}</p>
                                <p class="mt-1 truncate text-sm text-neutral-500">{{ $related->artist_name ?: $related->channel_title }}</p>
                            </button>
                        @endforeach
                    </div>
                </section>

                <section class="mt-16">
                    <div class="rounded-lg bg-[#0d0d0f] p-8">
                        <h2 class="text-2xl font-black">Song info</h2>
                        <dl class="mt-4 space-y-4 text-sm">
                            <div class="border-b border-white/10 pb-3">
                                <dt class="text-neutral-500">Artist</dt>
                                <dd class="mt-1 font-bold">{{ $song->artist_name ?: $song->channel_title }}</dd>
                            </div>
                            <div class="border-b border-white/10 pb-3">
                                <dt class="text-neutral-500">Source</dt>
                                <dd class="mt-1 font-bold">Official YouTube embed</dd>
                            </div>
                        </dl>
                    </div>
                </section>
            </main>

            @include('karaoke.partials.player', ['activeRoom' => $activeRoom, 'queueItems' => $queueItems])
        </div>
    </body>
</html>
