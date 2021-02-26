import { InertiaApp } from '@inertiajs/inertia-vue'
import Vue from 'vue'
import route from 'ziggy';
import { Ziggy } from './ziggy';

import Vuetify from 'vuetify'

window.axios = require('axios')

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
window.axios.defaults.headers.common['X-CSRF-TOKEN'] = window.Laravel.csrfToken

require('bootstrap')
window.$ = window.jQuery = require('jquery')
window.route = route
window.Ziggy = Ziggy

Vue.use(InertiaApp)
Vue.use(Vuetify)

let vuetify = new Vuetify({})

Vue.mixin({
    methods: {
        route: (name, params, absolute) => route(name, params, absolute, Ziggy),
    },
});

const app = document.getElementById('app')

new Vue({
    render: h => h(InertiaApp, {
        props: {
            initialPage: JSON.parse(app.dataset.page),
            resolveComponent: name => require(`./Pages/${name}`).default,
        },
    }),
    vuetify,
}).$mount(app)
