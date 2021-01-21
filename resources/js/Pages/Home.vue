<template>
    <layout title="Home">
        <template v-if="$parent.$page.user">
            <div class="row">
                <div class="col-md-6">
                    <h2>Your Contracts</h2>
                    <div class="list-group" v-if="playerInfo">
                        <div class="list-group-item" v-for="contract in playerInfo.contracts.contractsList">
                            <h3>{{ contract.contract.name }} - {{ contract.coopIdentifier }}</h3>
                            
                        </div>
                    </div>

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
                            <EggFormater :eggs="playerInfo.game.soulEggsD" />
                        </p>

                        <p>
                            Prestige Eggs:
                            {{ playerInfo.game.eggsOfProphecy }}
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

    export default {
        components: {
            Layout,
            EggFormater,
        },
        props: {
            guilds: Object,
            playerInfo: Object,
            user: Object,
        }
    }
</script>
