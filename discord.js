'use strict';

const { Client, Intents } = require('discord.js');
const client = new Client({ intents: [Intents.FLAGS.GUILDS, Intents.FLAGS.GUILD_MESSAGES] });
const axios = require('axios');
const _ = require('lodash');

require('dotenv').config();

client.on('ready', () => {
    console.log('bot is ready');
    axios.get(process.env.DISCORD_API_URL + '/api/slashes')
        .then(response => {
            response.data.map(command => {
                client.application.commands.create(command)

                // client.guilds.cache.get('741338066496520192').commands.create(command);
            })
        })
    ;

});

client.on('interaction', interaction => {
    if (!interaction.isCommand()) {
        return
    }

    interaction.reply('Loading')

    let content = interaction.commandName

    let message = {
        atBotUser: 'eb',
        channel: {
            id: interaction.channelID,
            guild: {
                id: interaction.guildID,
            }
        },
        content: content,
        author: {
            id: interaction.user.id,
            username: interaction.user.username,
        }
    }

    let reply = '';

    axios.post(process.env.DISCORD_API_URL + '/api/discord-message', message)
        .then(function (response) {
            if (response.data.message) {
                if (_.isArray(response.data.message)) {
                    reply = 'More than one message. Not done yet';
                } else {
                    reply = response.data.message;
                }
            } else {
                reply = 'I have nothing to say.';
            }
        })
        .catch(function (error) {
            reply = 'An error has occurred.';
        })
        .then(() => {
            interaction.editReply(reply)
        })
    ;
})

client.on('message', message => {
    let atBotUser = 'eb!';
    message.content = message.content.toLowerCase();

    if (message.author.bot || !message.content.startsWith(atBotUser)) {
        return;
    }

    message.channel.startTyping();

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

            message.channel.stopTyping();
        })
        .catch(function (error) {
            message.channel.send('An error has occurred.');

            message.channel.stopTyping();
        })
    ;
})

client.login(process.env.DISCORD_BOT_TOKEN);
