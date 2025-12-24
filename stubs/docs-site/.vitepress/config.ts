import { defineConfig } from 'vitepress'

export default defineConfig({
    title: 'Project Docs',
    description: 'Internal documentation',
    base: '/',

    themeConfig: {
        nav: [
            { text: 'Home', link: '/' },
            { text: 'Getting Started', link: '/getting-started/' },
            { text: 'Architecture', link: '/architecture/' },
        ],

        sidebar: [
            {
                text: 'Getting Started',
                items: [{ text: 'Developer Setup', link: '/getting-started/' }],
            },
            {
                text: 'Architecture',
                items: [{ text: 'Overview', link: '/architecture/' }],
            },
            {
                text: 'Code Patterns',
                items: [
                    { text: 'Overview', link: '/patterns/' },
                    { text: 'Actions', link: '/patterns/actions' },
                    { text: 'DTOs', link: '/patterns/dtos' },
                    { text: 'Services', link: '/patterns/services' },
                ],
            },
        ],

        socialLinks: [{ icon: 'github', link: 'https://github.com' }],

        search: {
            provider: 'local',
        },

        outline: {
            level: [2, 3],
        },
    },
})
