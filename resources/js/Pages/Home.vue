<template>
    <layout title="Home">
        <template v-if="$parent.$page.user">
            <div class="row">
                <div class="col-md-8">
                    <h2>Discord Servers With EggBert</h2>

                    <div class="list-group">
                        <a v-for="guild in guilds" class="list-group-item list-group-item-action flex-column" :href="route('guild.index', {'guildId': guild.id})">
                            <h5 class="mb-1">{{ guild.name }}</h5>
                        </a>
                    </div>

                    <h2>Your Contracts</h2>
                    <div class="list-group" v-if="playerInfo">
                        <div class="list-group-item" v-for="contract in playerInfo.contracts.activeContracts">
                            <h3>
                                {{ contract.props.name }}
                                <template v-if="contract.id">
                                    - {{ contract.id }}
                                </template>
                            </h3>
                            <div class="text-center">
                                <p>
                                    Time Left:
                                    <TimeLeft :seconds-left="getCoopTimeLeft(contract)" />
                                </p>

                                <p>
                                    Estimate Completion:
                                     <TimeLeft :seconds-left="estimateCompletion" v-if="estimateCompletion" />
                                    <template v-if="estimateCompletion === 0">Complete</template>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 list-group">
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
    import TimeLeft from '../Components/TimeLeft'

    export default {
        components: {
            Layout,
            EggFormater,
            TimeLeft,
        },
        props: {
            guilds: Object,
            playerInfo: Object,
            user: Object,
        },
        computed: {
            estimateCompletion(contract) {
                if (this.eggsLeftToGet < 0) {
                    return 0
                }

                let currentRateInSeconds = this.totalRate
                return Math.ceil(this.eggsLeftToGet / currentRateInSeconds)
            },
        },
        methods: {
            getCoopTimeLeft(contract) {
                return Math.floor((contract.started + contract.props.durationSeconds) - (Date.now() / 1000))
            },
        },
    }
</script>
