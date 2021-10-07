<template>
    <layout :title="guildModel.name + ' - Settings'">
        <form method="post" :action="route('guild.settingsSave', {'guildId': guildModel.discord_id})">
            <input type="hidden" name="_token" :value="csrfToken" />

            <h3>Guild Settings</h3>
            <div class="form-group">
                <v-select
                    :items="channelCategories"
                    label="Coop Channel Category"
                    item-text="name"
                    item-value="id"
                    v-model="guildModel.coop_channel_parent"
                    name="coop_channel_parent"
                ></v-select>

                <v-select
                    :items="guildModel.roles"
                    label="Role to add to Coop Channels"
                    item-text="name"
                    item-value="discord_id"
                    v-model="guildModel.role_to_add_to_coop"
                    name="role_to_add_to_coop"
                    multiple
                    chips
                ></v-select>

                <v-select
                    :items="trackingOptions"
                    label="Sort by in tracker"
                    item-text="text"
                    item-value="option"
                    v-model="guildModel.tracker_sort_by"
                    name="tracker_sort_by"
                ></v-select>

                <v-checkbox
                  v-model="guildModel.show_link_on_status"
                  label="Show link on status"
                  name="show_link_on_status"
                  :value="1"
                ></v-checkbox>
            </div>

            <h3>Roles</h3>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Role Name</th>
                        <th>Is Admin</th>
                        <th>Can add coops</th>
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
                            <input type="hidden" :name="'roles[' + role.id + '][can_add]'" value="0" />
                            <input
                                type="checkbox"
                                :name="'roles[' + role.id + '][can_add]'"
                                value="1"
                                :checked="role.can_add"
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
            channelCategories: Array,
        },
        data() {
            return {
                trackingOptions: [
                    {
                        option: 'eggs_per_second',
                        text: 'Eggs per second',
                    },
                    {
                        option: 'earning_bonus',
                        text: 'Earning Bonus',
                    }
                ]
            }
        },
        computed: {
            csrfToken() {
                return this.$page.csrf_token
            }
        }
    }
</script>
