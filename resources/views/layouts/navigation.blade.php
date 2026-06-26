<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        <x-icon name="layout-dashboard" />
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('admin.students.index')" :active="request()->routeIs('admin.students.*')">
                            <x-icon name="users" />
                            {{ __('Students') }}
                        </x-nav-link>

                        <x-nav-link :href="route('admin.student-groups.index')" :active="request()->routeIs('admin.student-groups.*')">
                            <x-icon name="users-round" />
                            {{ __('Groups') }}
                        </x-nav-link>

                        <x-nav-link :href="route('admin.categories.index')" :active="request()->routeIs('admin.categories.*')">
                            <x-icon name="tag" />
                            {{ __('Categories') }}
                        </x-nav-link>

                        <x-nav-link :href="route('admin.questions.index')" :active="request()->routeIs('admin.questions.*')">
                            <x-icon name="circle-help" />
                            {{ __('Questions') }}
                        </x-nav-link>

                        <x-nav-link :href="route('admin.exams.index')" :active="request()->routeIs('admin.exams.*')">
                            <x-icon name="clipboard-list" />
                            {{ __('Exams') }}
                        </x-nav-link>

                        <x-nav-link :href="route('admin.results.index')" :active="request()->routeIs('admin.results.*')">
                            <x-icon name="chart-bar" />
                            {{ __('Results') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <x-icon name="chevron-down" class="h-4 w-4" />
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            <span class="inline-flex items-center gap-2">
                                <x-icon name="user" />
                                {{ __('Profile') }}
                            </span>
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                <span class="inline-flex items-center gap-2">
                                    <x-icon name="log-out" />
                                    {{ __('Log Out') }}
                                </span>
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                <span class="inline-flex items-center gap-2">
                    <x-icon name="layout-dashboard" />
                    {{ __('Dashboard') }}
                </span>
            </x-responsive-nav-link>

            @if (Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.students.index')" :active="request()->routeIs('admin.students.*')">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="users" />
                        {{ __('Students') }}
                    </span>
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('admin.student-groups.index')" :active="request()->routeIs('admin.student-groups.*')">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="users-round" />
                        {{ __('Groups') }}
                    </span>
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('admin.categories.index')" :active="request()->routeIs('admin.categories.*')">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="tag" />
                        {{ __('Categories') }}
                    </span>
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('admin.questions.index')" :active="request()->routeIs('admin.questions.*')">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="circle-help" />
                        {{ __('Questions') }}
                    </span>
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('admin.exams.index')" :active="request()->routeIs('admin.exams.*')">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="clipboard-list" />
                        {{ __('Exams') }}
                    </span>
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('admin.results.index')" :active="request()->routeIs('admin.results.*')">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="chart-bar" />
                        {{ __('Results') }}
                    </span>
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    <span class="inline-flex items-center gap-2">
                        <x-icon name="user" />
                        {{ __('Profile') }}
                    </span>
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        <span class="inline-flex items-center gap-2">
                            <x-icon name="log-out" />
                            {{ __('Log Out') }}
                        </span>
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
