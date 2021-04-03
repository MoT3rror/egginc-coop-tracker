<template>
    <layout :title="guildModel.name + ' - Settings'">
        <form method="post" :action="route('guild.settingsSave', {'guildId': guildModel.discord_id})">
            <input type="hidden" name="_token" :value="csrfToken" />

            <h3>Roles</h3>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Role Name</th>
                        <th>Is Admin</th>
                        <th>Show Member on Roster</th>
                        <th>Show Role for member</th>
                        <th>Part of team</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="role in guildModel.roles">
                        <td>
                            {{ role.name }}
                        </td>
                        <td>
                            <input type="hidden" :name="'roles[' + role.id + '][is_admin]'" value="0" />
                            <input
                                type="checkbox"
                                :name="'roles[' + role.id + '][is_admin]'"
                                value="1"
                                :checked="role.is_admin"
                            />
                        </td>
                        <td>
                            <input type="hidden" :name="'roles[' + role.id + '][show_members_on_roster]'" value="0" />
                            <input
                                type="checkbox"
                                :name="'roles[' + role.id + '][show_members_on_roster]'"
                                value="1"
                                :checked="role.show_members_on_roster"
                            />
                        </td>
                        <td>
                            <input type="hidden" :name="'roles[' + role.id + '][show_role]'" value="0" />
                            <input
                                type="checkbox"
                                :name="'roles[' + role.id + '][show_role]'"
                                value="1"
                                :checked="role.show_role"
                            />
                        </td>
                        <td>
                            <input type="hidden" :name="'roles[' + role.id + '][part_of_team]'" value="0" />
                            <input
                                type="checkbox"
                                :name="'roles[' + role.id + '][part_of_team]'"
                                value="1"
                                :checked="role.part_of_team"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>

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
            guildModel: Object,
        },
        computed: {
            csrfToken() {
                return this.$page.csrf_token
            }
        }
    }
</script>
