<template>
    <layout title="Home">
        <template v-if="$parent.$page.user">
            <div class="row">
                <div class="col-md-6">
                    <h2>Discord Servers With EggBert</h2>

                    <div class="list-group">
                        <a v-for="guild in guilds" class="list-group-item list-group-item-action flex-column" :href="route('guild.index', {'guildId': guild.id})">
                            <a :href="route('guild.settings', {'guildId': guild.id})" class="float-right">
                                <i class="fa fa-cog" aria-hidden="true" v-if="guild.isAdmin"></i>
                            </a>
                            <h5 class="mb-1">{{ guild.name }}</h5>
                        </a>
                    </div>
                </div>

                <div class="col-md-6 list-group">
                    <h2>Player Info</h2>
                    <div class="list-group-item" v-if="playerInfo">
                        <p>
                            Soul Eggs:
                            <EggFormater :eggs="playerInfo.progress.soulEggs" />
                        </p>

                        <p>
                            Prestige Eggs:
                            {{ playerInfo.progress.prophecyEggs }}
                        </p>

                        <p>
                            Rank:
                            {{ user.player_egg_rank }}
                        </p>

                        <p>
                            Earning Bonus:
                            {{ user.player_earning_bonus_formatted }}
                        </p>

                        <p>
                            Prestige Eggs Needed for Next Rank:
                            {{ user.p_e_needed_for_next_rank }}
                        </p>

                        <p>
                            Soul Eggs Needed for Next Rank:
                            <EggFormater :eggs="user.soul_eggs_needed_for_next_rank" />
                        </p>
                    </div>
                </div>

                <div class="col-lg-12">
                    <UserStats :userStats="user.user_stats" label="Golden Eggs" dataKey="golden_eggs"></UserStats>
                    <UserStats :userStats="user.user_stats" label="Drones" dataKey="drones"></UserStats>
                    <UserStats :userStats="user.user_stats" label="Elite Drones" dataKey="elite_drones"></UserStats>
                    <UserStats :userStats="user.user_stats" label="PE" dataKey="prestige_eggs"></UserStats>
                    <UserStats :userStats="user.user_stats" label="SE" dataKey="soul_eggs_float" :eggFormat="true"></UserStats>
                    <UserStats :userStats="user.user_stats" label="Earning Bonus" dataKey="earning_bonus" :eggFormat="true" :showRole="true"></UserStats>
                </div>
            </div>
        </template>

        <template v-if="!$parent.$page.user">
            <p>Sign in with Discord to the left to view your guilds and Player Info</p>
        </template>
    </layout>
</template>

<script>
    import Layout from './Layout'
    import EggFormater from '../Components/EggFormater'
    import UserStats from '../Components/UserStats'

    export default {
        components: {
            Layout,
            EggFormater,
            UserStats,
        },
        props: {
            guilds: Object,
            playerInfo: Object,
            user: Object,
        },
        data: function() {
            return {}
        },
    }
</script>
