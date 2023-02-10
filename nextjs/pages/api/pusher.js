import Pusher from "pusher";

const pusher = new Pusher({
  appId: process.env.PUSHER_APP_ID,
  key: process.env.NEXT_PUBLIC_PUSHER_APP_KEY,
  secret: process.env.PUSHER_APP_SECRET,
  cluster: process.env.NEXT_PUBLIC_PUSHER_APP_CLUSTER,
  useTLS: true
});

export default async function handler(req, res) {
  console.log(process.env.PUSHER_APP_SECRET)
  console.log(process.env.NEXT_PUBLIC_PUSHER_APP_KEY)
  const { message, sender } = req.body;
  const response = await pusher.trigger("partymechanics", "esp-test-event", {
    message,
    sender,
  });

  res.json({ message: `${message} completed` });
}
