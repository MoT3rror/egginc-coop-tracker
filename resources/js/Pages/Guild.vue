<template>
    <layout :title="guildModel.name">
        <template v-if="!guildModel.is_bot_member_of">
            some link to add the bot to the server
        </template>

        <template v-if="guildModel.is_bot_member_of">
            <div>
                <h4>Current Contracts</h4>
                <ul>
                    <li v-for="contract in currentContracts">
                        <a :href="route('contract-guild-status', {'guildId': guildModel.discord_id, 'contractId': contract.id})">
                            {{ contract.name }}
                        </a>
                        - 
                        <a :href="route('make-coops', {'guildId': guildModel.discord_id, 'contractId': contract.id})">
                            Make coops
                        </a>
                    </li>
                </ul>
                <h4>Previous Contracts</h4>
            </div>

            <div>
                <h4>Player List</h4>

                <v-data-table
                    :headers="headers"
                    :items="members"
                    :disable-filtering="true"
                    :disable-pagination="true"
                    :hide-default-footer="true"
                >
                    <template v-slot:item.roles="{ item }">
                        {{ getUserRoles(item.roles) }}
                    </template>
                    <template v-slot:item.player_earning_bonus="{ item }">
                        <template v-if="item.player_egg_rank">
                            {{ item.player_earning_bonus_formatted }}
                            ({{ item.player_egg_rank }})
                        </template>
                        
                    </template>
                    <template v-slot:item.soul_eggs="{ item }">
                        <EggFormater :eggs="item.soul_eggs" />
                    </template>
                </v-data-table>
            </div>
        </template>
    </layout>
</template>

<script>
    import Layout from './Layout'
    import EggFormater from '../Components/EggFormater'
    import _ from 'lodash'

    export default {
        components: {
            Layout, EggFormater,
        },
        props: {
            guildModel: Object,
            currentContracts: Array,
        },
        data() {
            return {
                headers: [
                    {text: 'Username', value: 'username'},
                    {text: 'Roles', value: 'roles'},
                    {
                        text: 'Earning Bonus (Rank)',
                        value: 'player_earning_bonus',
                    },
                    {text: 'Soul Eggs', value: 'soul_eggs'},
                    {text: 'Golden Eggs', value: 'eggs_of_prophecy'},
                    {text: 'Drones', value: 'drones'},
                ]
            }
        },
        computed: {
            members() {
                return _.filter(this.guildModel.members, (member) => {
                    return _.filter(member.roles, 'show_members_on_roster').length >= 1;
                })
            }
        },
        methods: {
            getUserRoles(roles) {
                return _
                    .chain(roles)
                    .filter((role) => role.show_role)
                    .map((role) => role.name)
                    .join(',')
            },
        },
    }
</script>
