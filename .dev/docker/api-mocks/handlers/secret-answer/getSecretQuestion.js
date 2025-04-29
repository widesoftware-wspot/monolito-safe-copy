
module.exports = async function (req, res) {

    const hasError = req.headers['x-error-code'] !== undefined;
    if (hasError){
        try {
            const errorCode = req.headers['x-error-code'];
            res
                .status(errorCode)
                .header('Content-Language', 'pt_BR')
                .json({"msg": "An error has been ocurred"});
        }catch (e) {
            res
                .status(500)
                .header('Content-Language', 'pt_BR')
                .json({"msg": "An error has been ocurred"});
        }
    }else {
        const language = req.query.language;
        if (language == 'es'){
            res
                .status(200)
                .header('Content-Language', 'pt_BR')
                .json([
                    {"id":1, "question": "¿Cuál es tu palabra secreta?", "language": "es"},
                    {"id":2, "question": "¿Cual es tu numero de la suerte?", "language": "es"},
                    {"id":3, "question": "¿Cuál es el nombre de su mejor amigo?", "language": "es"},
                    {"id":4, "question": "¿Cual es tu comida favorita?", "language": "es"},
                    {"id":5, "question": "¿Cual es tu deporte favorito?", "language": "es"},
                    {"id":6, "question": "¿Cuál es el trabajo de tus sueños?", "language": "es"},
                    {"id":7, "question": "¿Cómo se llamaba la primera escuela que estudiaste?", "language": "es"}
                ]);
        }else if (language == 'en'){
            res
                .status(200)
                .header('Content-Language', 'pt_BR')
                .json([
                    {"id":1, "question": "What's your secret word?", "language": "en"},
                    {"id":2, "question": "What's your lucky number?", "language": "en"},
                    {"id":3, "question": "What is your best friend's name?", "language": "en"},
                    {"id":4, "question": "What's your favourite food?", "language": "en"},
                    {"id":5, "question": "What is your favorite sport?", "language": "en"},
                    {"id":6, "question": "What is your dream job?", "language": "en"},
                    {"id":7, "question": "What was the name of the first school you studied?", "language": "en"}
                ]);
        }else {
            res
                .status(200)
                .header('Content-Language', 'pt_BR')
                .json([
                    {"id":1, "question": "Qual é a sua palavra secreta?", "language": "pt_br"},
                    {"id":2, "question": "Qual seu número da sorte?", "language": "pt_br"},
                    {"id":3, "question": "Qual nome do seu melhor amigo(a)?", "language": "pt_br"},
                    {"id":4, "question": "Qual é a sua comida favorita?", "language": "pt_br"},
                    {"id":5, "question": "Qual é o seu esporte favorito?", "language": "pt_br"},
                    {"id":6, "question": "Qual é o emprego dos seus sonhos?", "language": "pt_br"},
                    {"id":7, "question": "Qual nome da primeira escola que você estudou?", "language": "pt_br"}
                ]);
        }
    }
};