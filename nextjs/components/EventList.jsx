import Avatar from "./Avatar"
import { useState, useEffect } from 'react'

export default function Events() {
  const [isLoading, setLoading] = useState(false)
  const [messages, setMessages] = useState([]);
  let allMessages = [];

    useEffect(() => {
      setLoading(true)

      const pusher = new Pusher('01a52d68bccce5e260bd', {
        cluster: 'us3'
      });

      const channel = pusher.subscribe('partymechanics');

      channel.bind('Party_ESP_Events', function (data) {
          allMessages.push(data);
          setMessages(allMessages);
          console.log(allMessages)
      });
      setLoading(false)
    }, [])

  if (isLoading) return <p>Loading...</p>

  return (
    <div>
      {JSON.stringify(allMessages)}
      <ul role="list" className="divide-y divide-gray-200">
      {messages.map(message => {
        return (
          <div className="list-group-item list-group-item-action py-3 lh-tight">
              <div className="d-flex w-100 align-items-center justify-content-between">
                  <strong className="mb-1">{message.username}</strong>
              </div>
              <div className="col-10 mb-1 small">{message.message}</div>
          </div>
        )
      })}
      </ul>
    </div>
  )
}