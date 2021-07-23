<template>
    <div data-app>
        <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
            <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="#">MoT3rror PlayGround</a>
            <button
                class="navbar-toggler position-absolute d-md-none"
                type="button"
                data-toggle="collapse"
                data-target="#sidebarMenu"
                aria-controls="sidebarMenu"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>
        </nav>

        <div class="container-fluid">
            <div class="row">
                <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                    <div class="sidebar-sticky pt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" :href="route('home')">
                                    <span data-feather="home"></span>
                                    Home
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" :href="route('current-contracts')">
                                    <span data-feather="file"></span>
                                    Current Contracts
                                </a>
                            </li>

                            <li class="nav-item" v-if="!$parent.$page.user">
                                <a class="nav-link" :href="route('discord-login')">Login from Discord</a>
                            </li>
                            <li class="nav-item" v-if="$parent.$page.user">
                                <a class="nav-link" :href="route('profile')">
                                    {{ $parent.$page.user.username }}
                                </a>
                            </li>
                            <li class="nav-item" v-if="$parent.$page.user">
                                <a class="nav-link" :href="route('logout')">Sign out</a>
                            </li>
                        </ul>
                    </div>
                </nav>

                <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">{{ title }}</h1>
                    </div>

                    <div>
                        <slot />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: {
            title: String
        },
        watch: {
            title: {
                immediate: true,
                handler(title) {
                    document.title = 'MoT3rror PlayGround - ' + title
                },
            },
        },
    }
</script>