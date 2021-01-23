<template>
    <div>
        <h3>
            {{ coop.code }}
            <i
                class="fas fa-user-plus"
                v-if="coop.public"
                title="Public"
            ></i>
        </h3>

        <p class="text-center">
            Time Left:
            <TimeLeft :seconds-left="coop.secondsUntilProductionDeadline" />
        </p>

        <div class="text-center">
            Estimate Completion:
            <TimeLeft :seconds-left="estimateCompletion" v-if="estimateCompletion" />
            <template v-if="estimateCompletion === 0">Complete</template>
        </div>

        <div class="text-center">
            Projected Eggs:
            <EggFormater :eggs="projectedEggs" />
            /
            <EggFormater :eggs="eggsTotalNeeded" />
        </div>

        <div :class="{
            'red-background': completeStatus === 'not-close',
            'green-background': completeStatus === 'completing',
            'orange-background': completeStatus === 'close-to-complete',
            'text-center': true,
        }">
            <EggFormater :eggs="rateNeededToComplete * 60 * 60" />
            / hr required to complete
        </div>

        <p class="text-center">
            Progress:
            <EggFormater :eggs="coop.eggsLaid" />
            /
            <EggFormater :eggs="eggsTotalNeeded" />
        </p>

        <div>
            <progress-bar
                :val="percentDone"
                :text="percentDone + '%'"
                text-position="middle"
            />
        </div>

        <h4>Members ({{ coop.members.length }} / {{ contractInfo.maxCoopSize }})</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Name - Boost Tokens</th>
                    <th>Egg Laid</th>
                    <th>Laying Rate</th>
                    <th>Contribution</th>
                    <th>Earning Bonus</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="member in coop.members">
                    <td>
                        {{ member.name }} - {{ member.tokens }}
                        <i
                            class="fas fa-suitcase-rolling"
                            v-if="!member.active"
                            title="Sleeping"
                        ></i>
                        <i
                            class="fas fa-bug"
                            v-if="member.timeCheatDetected"
                            title="Cheating"
                        ></i>
                    </td>
                    <td>
                        <EggFormater :eggs="member.eggsLaid" />
                    </td>
                    <td>
                        <EggFormater :eggs="member.eggsPerSecond * 60 * 60" />
                        / hr
                    </td>
                    <td>
                        {{ Math.round(member.eggs / totalSum * 10000) / 100 }}%
                    </td>
                    <td>
                        <EggFormater :eggs="Math.pow(10, member.earningBonusOom) * 100" />
                        -
                        <PlayerRole :soul-power="member.earningBonusOom" />
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td>Total - {{ totalBoosts }}</td>
                    <td>
                        <EggFormater :eggs="totalSum" />
                    </td>
                    <td>
                        <EggFormater :eggs="totalRate * 60 * 60" />
                        / hr
                    </td>
                    <td></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        <hr />
    </div>
</template>

<style type="text/css">
    .red-background {
        background-color: red;
    }
    .green-background {
        background-color: #00800085;
    }
    .orange-background {
        background-color: orange;
    }
</style>

<script>
    import TimeLeft from '../Components/TimeLeft'
    import EggFormater from '../Components/EggFormater'
    import ProgressBar from 'vue-simple-progress'
    import PlayerRole from '../Components/PlayerRole'

    export default {
        components: {
            TimeLeft, EggFormater, ProgressBar, PlayerRole
        },
        props: {
            coop: Object,
            contractInfo: Object,
        },
        computed: {
            percentDone() {
                return Math.round(this.coop.eggsLaid / this.eggsTotalNeeded * 100)
            },
            coopType() {
                // elite = 0, standard = 1
                return 0
            },
            eggsTotalNeeded() {
                let rewards = this.contractInfo.rewardTiers[this.coopType].rewards
                console.log(rewards)
                return rewards[rewards.length - 1].goal
            },
            eggsLeftToGet() {
                return this.eggsTotalNeeded - this.coop.eggsLaid
            },
            totalSum() {
                let total = 0
                this.coop.members.forEach((member) => {
                    total += member.eggsLaid
                })
                return total
            },
            totalRate() {
                let total = 0
                this.coop.members.forEach((member) => {
                    total += member.eggsPerSecond
                })
                return total
            },
            totalBoosts() {
                let total = 0
                this.coop.members.forEach((member) => {
                    total += member.tokens ? member.tokens : 0
                })
                return total
            },
            rateNeededToComplete() {
                if (this.eggsLeftToGet <= 0) {
                    return 0;
                }

                return this.eggsLeftToGet / Math.abs(Math.floor(this.coop.secondsUntilProductionDeadline))
            },
            estimateCompletion() {
                if (this.eggsLeftToGet < 0) {
                    return 0
                }

                let currentRateInSeconds = this.totalRate
                return Math.ceil(this.eggsLeftToGet / currentRateInSeconds)
            },
            completeStatus() {
                if (this.rateNeededToComplete < this.totalRate) {
                    return 'completing'
                }

                if ((this.rateNeededToComplete * .6) < this.totalRate) {
                    return 'close-to-complete'
                }

                return 'not-close'
            },
            projectedEggs() {
                return this.coop.eggs + (this.totalRate * this.coop.secondsUntilProductionDeadline)
            }
        },
    }
</script>
