<template>
    <layout title="Current Contracts">
        <table class="table">
            <thead>
                <tr>
                    <th>Contract Name</th>
                    <th>Identifier</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="contract in contracts">
                    <td>
                        <a :href="route('contract-status', {'contractId': contract.id})">
                            {{ contract.name }}
                        </a>
                    </td>
                    <td>
                        {{ contract.identifier }}
                    </td>
                </tr>
            </tbody>
        </table>
    </layout>
</template>

<script>
    import Layout from './Layout'

    export default {
        components: {
            Layout,
        },
        props: {
            contracts: Object
        }
    }
</script>
