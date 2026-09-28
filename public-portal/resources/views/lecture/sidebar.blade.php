{{--
    Michael, 2026-09-07 -- extracted into a real, shared partial. Found
    live: page.blade.php never actually defined a real @section('sidebar')
    at all (only @section('content')), meaning the layout's own
    @hasSection('sidebar') check correctly evaluated to false there --
    the sidebar simply never rendered on any real content page, only on
    home-base. A genuine, pre-existing bug from when page.blade.php was
    first built, not a new regression. Extracting this once now, rather
    than duplicating the same markup into every one of the ~60+ real
    content pages still to come.

    Expects $sections (the real, dynamic section/page/progress data from
    the Java side), $currentPageId (null on home-base itself; the real
    page id on a content page), and $resources (the real Resources list
    -- Glossary, FAQs, Abbreviations, VEO Resources, Bibliography --
    migration 045) so "is-current" highlighting reflects wherever the
    student actually is, rather than always pointing at Home Base.

    Michael, 2026-09-07 -- added the real Resources nav section, matching
    the live course's own nav order (after the ten numbered sections).
    "Submit a Question" has no real equivalent on our platform at all
    yet, so it's omitted entirely, matching the established pattern for
    dead nav items throughout this project. Survey is a real, genuinely
    external SurveyMonkey link -- kept as-is.
--}}
<div class="lect-sidebar">
    <div class="lect-quiz-progress">
        <a href="#" style="color:#7ec8ff; font-weight:700;">QUIZ PROGRESS &raquo;</a><br>
        @php
            $totalSections = count($sections);
            $touchedSections = collect($sections)->filter(fn ($s) => ! empty($s['touched']))->count();
            $percent = $totalSections > 0 ? round(($touchedSections / $totalSections) * 100) : 0;
        @endphp
        <span style="color:#7ec8ff; font-size:1.1rem; font-weight:700;">{{ $touchedSections }}</span> of
        <span style="font-size:1.1rem; font-weight:700;">{{ $totalSections }}</span> sect's ans'd &nbsp;({{ $percent }}%)
    </div>

    <ul class="lect-section-list">
        <li class="lect-section-item">
            <a href="{{ route('lecture.home-base') }}" class="lect-section-link {{ empty($currentPageId ?? null) ? 'is-current' : '' }}">
                <span class="lect-section-check is-touched">&#10004;</span>
                Home Base
            </a>
        </li>

        {{-- Michael, 2026-09-28 -- sections are collapsible (native
             <details>, no script needed), like the live course's accordion
             menu: only the section holding the current page starts open;
             clicking any other section heading expands it. Sections with no
             captured pages yet stay plain, non-expandable headings. --}}
        @foreach ($sections as $section)
            @php
                $isCurrentSection = collect($section['pages'] ?? [])->contains(fn ($p) => ($currentPageId ?? null) == $p['id'])
                    || (! empty($currentQuizId ?? null) && ($section['quizId'] ?? null) == $currentQuizId);
            @endphp
            <li class="lect-section-item">
                @if (! empty($section['pages']))
                <details class="lect-section-toggle" @if ($isCurrentSection) open @endif>
                <summary class="lect-section-link">
                    <span class="lect-section-check {{ ! empty($section['touched']) ? 'is-touched' : 'is-locked' }}">
                        {{ ! empty($section['touched']) ? '✔' : '' }}
                    </span>
                    {{ $section['name'] }}
                </summary>
                @else
                <span class="lect-section-link" style="cursor:default;">
                    <span class="lect-section-check {{ ! empty($section['touched']) ? 'is-touched' : 'is-locked' }}">
                        {{ ! empty($section['touched']) ? '✔' : '' }}
                    </span>
                    {{ $section['name'] }}
                </span>
                @endif

                @if (! empty($section['pages']))
                    <ul class="lect-page-list">
                        @foreach ($section['pages'] as $page)
                            <li class="lect-page-item">
                                @if (! empty($page['unlocked']))
                                    <a href="{{ route('lecture.page', $page['id']) }}" class="lect-page-link {{ ! empty($page['read']) ? 'is-read' : '' }} {{ ($currentPageId ?? null) == $page['id'] ? 'is-current' : '' }}">
                                        {{ $page['title'] }}
                                    </a>
                                @else
                                    <span class="lect-page-link is-locked" title="Disabled because you have not yet read the previous page.">
                                        {{ $page['title'] }}
                                    </span>
                                @endif
                            </li>
                        @endforeach

                        {{-- Michael, 2026-09-07 -- real quiz-taking flow. "Section Quiz" only
                             links anywhere real once: (1) a quiz has genuinely been seeded for
                             this section, (2) the section itself is unlocked, and (3) every real
                             page in the section has been read -- matching the same established
                             pattern used everywhere else in this sidebar for content that isn't
                             reachable yet (plain, non-clickable text instead of a dead link). --}}
                        @php
                            $allPagesRead = collect($section['pages'])->every(fn ($p) => ! empty($p['read']));
                        @endphp
                        <li class="lect-page-item">
                            @if (! empty($section['quizId']) && ! empty($section['unlocked']) && $allPagesRead)
                                <a href="{{ route('lecture.quiz', $section['quizId']) }}" class="lect-page-link {{ ! empty($section['quizPassed']) ? 'is-read' : '' }}">
                                    Section Quiz
                                </a>
                            @else
                                <span class="lect-page-link is-locked" title="Disabled because you have not yet read all the pages for this section.">
                                    Section Quiz
                                </span>
                            @endif
                        </li>
                    </ul>
                </details>
                @endif
            </li>
        @endforeach

        @if (! empty($resources))
            <li class="lect-section-item">
                <details class="lect-section-toggle" @if (! empty($currentResourceSlug ?? null)) open @endif>
                <summary class="lect-section-link">
                    <span class="lect-section-check"></span>
                    Resources
                </summary>
                <ul class="lect-page-list">
                    @foreach ($resources as $resource)
                        <li class="lect-page-item">
                            <a href="{{ route('lecture.resource', $resource['slug']) }}" class="lect-page-link {{ ($currentResourceSlug ?? null) == $resource['slug'] ? 'is-current' : '' }}">
                                {{ $resource['title'] }}
                            </a>
                        </li>
                    @endforeach
                    <li class="lect-page-item">
                        <a href="https://www.surveymonkey.com/r/BWN3Q6P" target="_blank" rel="noopener" class="lect-page-link">
                            Survey
                        </a>
                    </li>
                </ul>
                </details>
            </li>
        @endif
    </ul>
</div>
