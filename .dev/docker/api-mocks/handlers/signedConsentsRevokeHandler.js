
module.exports = async function (req, res) {

    const guestId = Number(req.params.guest_id)
    const consentId = req.query.consent_id

    res
        .status(200)
        .header('Content-Language', 'en_US')
        .json(getResponseBody(guestId, consentId));
};

const getResponseBody = (guestId, consentId) => ({
    "message": `signed_consent_id: ${consentId} from guest_id: ${guestId} was deleted`
})
