<template>
    <layout title="Home">
        <template v-if="$parent.$page.user">
            <div class="row">
                <div class="col-md-6">
                    <h2>Your Contracts</h2>
                    <div class="list-group" v-if="playerInfo">
                        <div class="list-group-item" v-for="contract in playerInfo.contracts.activeContracts">
                            <h3>{{ contract.props.name }} - {{ contract.id }}</h3>
                            
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

        <template>
        <v-data-table
            :headers="headers"
            :items="desserts"
            :items-per-page="5"
            class="elevation-1"
        ></v-data-table>
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
        data: function() {
        return {
            headers: [
            {
                text: 'Dessert (100g serving)',
                align: 'start',
                sortable: false,
                value: 'name',
            },
            { text: 'Calories', value: 'calories' },
            { text: 'Fat (g)', value: 'fat' },
            { text: 'Carbs (g)', value: 'carbs' },
            { text: 'Protein (g)', value: 'protein' },
            { text: 'Iron (%)', value: 'iron' },
            ],
            desserts: [
            {
                name: 'Frozen Yogurt',
                calories: 159,
                fat: 6.0,
                carbs: 24,
                protein: 4.0,
                iron: '1%',
            },
            {
                name: 'Ice cream sandwich',
                calories: 237,
                fat: 9.0,
                carbs: 37,
                protein: 4.3,
                iron: '1%',
            },
            {
                name: 'Eclair',
                calories: 262,
                fat: 16.0,
                carbs: 23,
                protein: 6.0,
                iron: '7%',
            },
            {
                name: 'Cupcake',
                calories: 305,
                fat: 3.7,
                carbs: 67,
                protein: 4.3,
                iron: '8%',
            },
            {
                name: 'Gingerbread',
                calories: 356,
                fat: 16.0,
                carbs: 49,
                protein: 3.9,
                iron: '16%',
            },
            {
                name: 'Jelly bean',
                calories: 375,
                fat: 0.0,
                carbs: 94,
                protein: 0.0,
                iron: '0%',
            },
            {
                name: 'Lollipop',
                calories: 392,
                fat: 0.2,
                carbs: 98,
                protein: 0,
                iron: '2%',
            },
            {
                name: 'Honeycomb',
                calories: 408,
                fat: 3.2,
                carbs: 87,
                protein: 6.5,
                iron: '45%',
            },
            {
                name: 'Donut',
                calories: 452,
                fat: 25.0,
                carbs: 51,
                protein: 4.9,
                iron: '22%',
            },
            {
                name: 'KitKat',
                calories: 518,
                fat: 26.0,
                carbs: 65,
                protein: 7,
                iron: '6%',
            },
            ],
        }
        },
    }
</script>
