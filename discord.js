'use strict';

const { Client, Intents } = require('discord.js');
const client = new Client({
    intents: [
        Intents.FLAGS.GUILDS, Intents.FLAGS.GUILD_MESSAGES, Intents.FLAGS.GUILD_MESSAGE_TYPING,
        Intents.FLAGS.DIRECT_MESSAGES, Intents.FLAGS.DIRECT_MESSAGE_TYPING,
    ],
    partials: ['MESSAGE', 'CHANNEL'],
});
const axios = require('axios');
const _ = require('lodash');

require('dotenv').config();

client.on('ready', () => {
    console.log('bot is ready');
})

client.on('interactionCreate', interaction => {
    if (!interaction.isCommand()) {
        return
    }

    interaction.defer().then(() => {
        let content = interaction.commandName

        if (interaction.options) {
            _.forEach(interaction.options._hoistedOptions, option => {
                if (option) {
                    content += ' ' + option.value
                }
            })
        }

        let message = {
            atBotUser: 'eb',
            channel: {
                id: interaction.channelId,
                guild: {
                    id: interaction.guildId,
                }
            },
            content: content,
            author: {
                id: interaction.user.id,
                username: interaction.user.username,
            }
        }

        let reply = ''

        axios.post(process.env.DISCORD_API_URL + '/api/discord-message', message)
            .then(function (response) {
                if (response.data.message) {
                    if (_.isArray(response.data.message)) {
                        response.data.message.forEach((messageToSend, i) =>  {
                            if (i == 0) {
                                reply = messageToSend
                            } else {
                                interaction.channel.send(messageToSend)
                            }
                        })
                    } else {
                        reply = response.data.message;
                    }
                } else {
                    reply = 'I have nothing to say.';
                }
            })
            .catch(function (error) {
                console.log(error)
                reply = 'An error has occurred.';
            })
            .then(() => {
                interaction.editReply(reply)
            })
    })

})

client.on('message', message => {
    let atBotUser = 'eb!';
    message.content = message.content.toLowerCase();
    if (message.author.bot || !message.content.startsWith(atBotUser)) {
        return;
    }

    // message.channel.startTyping();

    let messageDetails = message.toJSON();
    messageDetails.atBotUser = atBotUser;
    messageDetails.channel = message.channel.toJSON();
    messageDetails.channel.guild = message.channel.guild ? message.channel.guild.toJSON() : {};
    messageDetails.author = message.author.toJSON();

    axios.post(process.env.DISCORD_API_URL + '/api/discord-message', messageDetails)
        .then(function (response) {
            if (response.data.message) {
                if (_.isArray(response.data.message)) {
                    _.forEach(response.data.message, function (messageToSend) {
                        message.channel.send(messageToSend)
                    })
                } else {
                    message.channel.send(response.data.message);
                }
            } else {
                message.channel.send('I have nothing to say.');
            }

            // message.channel.stopTyping();
        })
        .catch(function (error) {
            message.channel.send('An error has occurred.');

            // message.channel.stopTyping();
        })
    ;
})

client.login(process.env.DISCORD_BOT_TOKEN);
