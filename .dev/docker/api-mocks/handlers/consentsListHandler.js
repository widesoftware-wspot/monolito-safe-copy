
module.exports = async function (req, res) {

    const locale = req.header("Accept-Language")
    const clientId = Number(req.params.client_id)

    if (locale === 'en_US') {
        res
            .status(200)
            .header('Content-Language', 'en_US')
            .json(getEnglishConsent(clientId));
    } else if (locale === 'es_ES') {
        res
            .status(200)
            .header('Content-Language', 'es_ES')
            .json(getSpanishConsent(clientId));
    } else {
        res
            .status(200)
            .header('Content-Language', 'pt_BR')
            .json(getPortuguesConsent(clientId));
    }
};

const getEnglishConsent = (clientId) => ({
    "id": "bd49c62f-490b-4288-ad78-3787f3727f3c",
    "client_id": clientId,
    "consent_version": 1616970427,
    "status": "active",
    "conditions": [
        {
            "id": "1e32be9f-92c3-4149-aab6-7b830d5dcc1e",
            "description": "Sending messages and notifications (promotions, news and other relevant communications)"
        },
        {
            "id": "3bbedcee-41fe-407d-8263-758d1893c773",
            "description": "Analysis of user profile and behavior"
        },
        {
            "id": "c2d93074-e31b-49e2-bc41-b128c72fbeea",
            "description": "Data enrichment"
        }
    ]
})
const getSpanishConsent = (clientId) => ({
        "id": "bd49c62f-490b-4288-ad78-3787f3727f3c",
        "client_id": clientId,
        "consent_version": 1616970427,
        "status": "active",
        "conditions": [
            {
                "id": "1e32be9f-92c3-4149-aab6-7b830d5dcc1e",
                "description": "Envío de mensajes y notificaciones (promociones, noticias y otras comunicaciones relevantes)"
            },
            {
                "id": "3bbedcee-41fe-407d-8263-758d1893c773",
                "description": "Análisis del perfil y comportamiento de los usuarios"
            },
            {
                "id": "c2d93074-e31b-49e2-bc41-b128c72fbeea",
                "description": "Enriquecimiento de datos"
            }
        ]
    })
const getPortuguesConsent = (clientId) => ({
    "id": "bd49c62f-490b-4288-ad78-3787f3727f3c",
    "client_id": clientId,
    "consent_version": 1616970427,
    "status": "active",
    "conditions": [
        {
            "id": "1e32be9f-92c3-4149-aab6-7b830d5dcc1e",
            "description": "Envio de mensagens e notificações (promoções, notícias e demais comunicados pertinentes)"
        },
        {
            "id": "3bbedcee-41fe-407d-8263-758d1893c773",
            "description": "Análise do perfil e comportamento de usuários"
        },
        {
            "id": "c2d93074-e31b-49e2-bc41-b128c72fbeea",
            "description": "Enriquecimento de dados"
        }
    ]
})