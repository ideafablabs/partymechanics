import Pusher from "pusher";

export default async function handler(req, res) {
  const pusher = new Pusher({
    appId: process.env.PUSHER_APP_ID,
    key: process.env.NEXT_PUBLIC_PUSHER_APP_KEY,
    secret: process.env.PUSHER_APP_SECRET,
    cluster: process.env.NEXT_PUBLIC_PUSHER_APP_CLUSTER,
    useTLS: true
  });
  const { message, sender } = req.body;
  const response = await pusher.trigger("partymechanics", "Party_ESP_Events", {
    message,
    sender,
  });

  res.json({ message: `${message} completed` });
}
