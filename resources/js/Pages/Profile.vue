<template>
    <layout title="Profile">
        <form method="post" :action="route('profileSave')">
            <input type="hidden" name="_token" :value="csrfToken" />

            <v-text-field
                v-model="user.egg_inc_player_id"
                label="Egg Inc Player ID"
                name="egg_inc_player_id"
            ></v-text-field>

            <input type="hidden" name="keep_stats" value="0" />
            <v-checkbox
                v-model="user.keep_stats"
                label="Keep track of stats. Update daily at 12 AM CST."
                :value="true"
                name="keep_stats"
            ></v-checkbox>

            <button type="submit" class="btn btn-success">Save</button>
        </form>
    </layout>
</template>

<script>
    import Layout from './Layout'

    export default {
        components: {
            Layout,
        },
        props: {
            user: Object,
        },
        computed: {
            csrfToken() {
                return this.$page.csrf_token
            }
        },
    }
</script>
