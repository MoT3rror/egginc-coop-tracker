<template>
    <layout :title="guildModel.name">
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
            </div>

            <div>
                <h4>Player List</h4>

                <div style="margin-bottom: 50px">
                    <select class="form-control" v-model="filterByRole">
                        <option value="">Filter by Role</option>
                        <option v-for="role in roles" :value="role.id">
                            {{ role.name }}
                        </option>
                    </select>

                    <div class="form-check" style="margin-top: 10px">
                        <input class="form-check-input" type="checkbox" value="1" v-model="showEarningBonusRaw" />
                        <label class="form-check-label">
                            Show Raw Earning Bonus
                        </label>
                    </div>
                </div>

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
                        <template v-if="item.player_egg_rank && !showEarningBonusRaw">
                            {{ item.player_earning_bonus_formatted }}
                            ({{ item.player_egg_rank }})
                        </template>
                        
                        <template v-if="item.player_egg_rank && showEarningBonusRaw">
                            {{ item.player_earning_bonus }}
                        </template>
                    </template>
                    <template v-slot:item.soul_eggs="{ item }">
                        <EggFormater :eggs="item.soul_eggs" />
                    </template>
                    <template v-slot:item.highest_deflector_without_percent="{ item }">
                        {{ item.highest_deflector_without_percent }}%
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
                filterByRole: '',
                showEarningBonusRaw: false,
                headers: [
                    {text: 'Username', value: 'username'},
                    {text: 'Roles', value: 'roles'},
                    {
                        text: 'Earning Bonus (Rank)',
                        value: 'player_earning_bonus',
                    },
                    {text: 'Soul Eggs', value: 'soul_eggs'},
                    {text: 'Prophecy Eggs', value: 'eggs_of_prophecy'},
                    {text: 'Drones', value: 'drones'},
                    {text: 'Highest Deflector', value: 'highest_deflector_without_percent'},
                ],
            }
        },
        computed: {
            members() {
                return _.filter(this.guildModel.members, (member) => {
                    if (this.filterByRole) {
                        return _.filter(member.roles, ['id', this.filterByRole]).length >= 1
                    }

                    return _.filter(member.roles, 'show_members_on_roster').length >= 1
                })
            },
            roles() {
                return _
                    .chain(this.guildModel.roles)
                    .filter((role) => role.show_role)
                    .filter((role) => role.guild_id == this.guildModel.id)
                    .value()
            },
        },
        methods: {
            getUserRoles(roles) {
                return _
                    .chain(roles)
                    .filter((role) => role.show_role)
                    .filter((role) => role.guild_id == this.guildModel.id)
                    .map((role) => role.name)
                    .join(',')
            },
        },
    }
</script>
