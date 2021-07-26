<template>
    <layout :title="contractInfo.name + ' - Make Coops'">
        <h3>Settings</h3>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="number-of-coops">
                    Number of Coops
                </label>
                <input type="number" class="form-control" id="number-of-coops" v-model="numberOfCoops" />
            </div>

            <div class="form-group col-md-4">
                <label for="prefix">
                    Coop Name Prefix
                </label>
                <input type="text" class="form-control" id="prefix" v-model="prefix" />
            </div>

            <div class="form-group col-md-4">
                <label for="maxCoopSize">
                    Max Coop Size
                </label>
                <input type="number" class="form-control" id="maxCoopSize" disabled="disabled" v-model="contractInfo.maxCoopSize" />
            </div>
        </div>

        <div>
            <div v-for="(coop, number) in coops" class="card">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label :for="'coop_name_' + number">
                                Name
                            </label>
                            <input type="text" class="form-control" :id="'coop_name_' + number" v-model="coops[number].name" />
                        </div>

                        <div class="col-md-6">
                            <label :for="'coop_channel_id' + number">
                                Channel ID
                            </label>
                            <input type="text" class="form-control" v-model="coops[number].channel_id" />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6" v-for="(member, memberNumber) in coop.members">
                            <label :for="'coop_member_' + memberNumber + '_' + member">
                                Member {{ memberNumber + 1 }}
                            </label>
                            <select v-model="coops[number].members[memberNumber]" class="form-control">
                                <option></option>
                                <option v-for="user in availableMembers" :value="user.id" :disabled="user.selected">
                                    {{ user.text }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button v-on:click="save()" class="btn btn-success">Save</button>
        <button v-on:click="makeChannels()" class="btn btn-success">Make Channels</button>
    </layout>
</template>

<script>
    import Layout from './Layout'
    import _ from 'lodash'

    let generateRandomNumber = (numberOfCharacters) => {
        var randomValues = ''
        var stringValues = 'abcdefghjkmnpqrstuvwy'
        var sizeOfCharacter = stringValues.length
        for (var i = 0; i < numberOfCharacters; i++) {
            randomValues = randomValues+stringValues.charAt(Math.floor(Math.random() * sizeOfCharacter))
        }
        return randomValues
    }

    export default {
        components: {
            Layout
        },
        props: {
            contractInfo: Object,
            guild: Object,
            coopsDb: Array,
        },
        data() {
            return {
                coops: _.map(this.coopsDb, (coop) => {
                    coop.members = _.map(coop.members, 'user_id')
                    coop.members.length = this.contractInfo.maxCoopSize

                    return {
                        name: coop.coop,
                        members: coop.members,
                        channel_id: coop.channel_id,
                    }
                }),
                numberOfCoops: this.coopsDb.length,
                prefix: '',
                blankCoop: {
                    members: new Array(parseInt(this.contractInfo.maxCoopSize)),
                    name: '',
                    channel_id: '',
                },
                availableMembers: [],
            }
        },
        methods: {
            save() {
                let options = {
                    url : route('make-coops', {'guildId': this.guild.discord_id, 'contractId': this.contractInfo.id}),
                    method : 'post',
                    data: {coops:this.coops},
                }

                axios(options).then((response) => {
                    alert('The save was successfully. Boring message but great things come.')
                }).catch(err => {
                    alert('something bad happen')
                })
            },
            availableMembersReload() {
                this.availableMembers = _.chain(this.guild.members)
                    .filter('player_egg_rank')
                    .map((member) => {
                        member.selected = _.chain(this.coops).map('members').flatten().indexOf(member.id).value() !== -1

                        let roles = _.chain(member.roles)
                            .filter((role) => { return role.guild_id == this.guild.id })
                            .map((role) => { return role.name })
                            .join(', ')

                        member.text = member.username + ' - ' + member.egg_inc_username + ' - ' + member.player_egg_rank + ' - ' + roles + ' - ' + member.highest_deflector_without_percent + '%'
                        member.username_lower = _.toLower(member.username)
                        return member
                    })
                    .orderBy(['selected', 'username_lower'], ['asc', 'asc'])
                    .value()
            },
            makeChannels() {
                let options = {
                    url : route('make-channels', {'guildId': this.guild.discord_id, 'contractId': this.contractInfo.id}),
                    method : 'post',
                }

                axios(options).then((response) => {
                    alert('The channels have been made. Go Team!')
                }).catch(err => {
                    alert('something bad happen')
                })
            },
        },
        watch: {
            numberOfCoops: {
                handler() {
                    this.coops.length = this.numberOfCoops
                    for (var i = 0; i < this.numberOfCoops; i++) {
                        if (typeof(this.coops[i]) == 'undefined') {
                            this.$set(this.coops, i, _.cloneDeep(this.blankCoop))
                            this.coops[i].name = this.prefix + (i + 1) + generateRandomNumber(1)
                        }
                    }
                    this.availableMembersReload()
                },
                immediate: true,
            },
            coops: {
                handler() {
                    this.availableMembersReload()
                },
                deep: true,
            }
        }
    }
</script>

<style type="text/css" scoped>
    .card {
        margin-bottom: 10px;
    }
</style>