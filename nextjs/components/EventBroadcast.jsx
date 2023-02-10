import { useState, useEffect } from 'react'
import { getURL, classNames } from '../utils/helpers';
export default function EventBroadcast() {
  const [username, setUsername] = useState('username');
  const [message, setMessage] = useState(    `chipId: '1',
  chipName: 'Chip 1',
  status: 'online',
  triggerAction: 'test-action',
  UserTriggererId: 'user-name',
  UserCheckpoint: 'user-checkpoint',
  EventMessage: 'test message'`);
  const submit = async (e) => {
    e.preventDefault();

    await fetch(
      getURL()+'/api/pusher', {
        method: "POST",
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            message,
            username
        })
    });

    setMessage('');
  }
  return (
    <div className="space-y-6 sm:px-6 lg:col-span-9 lg:px-0">
      <form onSubmit={submit}>
        <div className="shadow sm:overflow-hidden sm">
          <div className="space-y-6 bg-white py-6 px-4 sm:p-6">
            <div>
              <h3 className="text-lg font-medium leading-6 text-gray-900">Broadcast Event</h3>
              <p className="mt-1 text-sm text-gray-500">
                This Json event message will be broadcast to all connected clients.
              </p>
            </div>

            <div className="grid grid-cols-3 gap-6">
              <div className="col-span-3 sm:col-span-2">
              <div
                className="d-flex align-items-center flex-shrink-0 p-3 link-dark text-decoration-none border-bottom">
                <input className="fs-5 fw-semibold" />
              </div>
                <label htmlFor="company-website" className="block text-sm font-medium text-gray-700">
                  Identifier/username
                </label>
                <div className="mt-1 flex shadow-sm">
                  <span className="inline-flex items-center border border-r-0 border-gray-300 bg-gray-50 px-3 text-gray-500 sm:text-sm">
                   Party_ESP_Events
                  </span>
                  <input
                    type="text"
                    name="username"
                    id="username"
                    value={username} 
                    onChange={e => setUsername(e.target.value)}
                    autoComplete="username"
                    className="block w-full min-w-0 flex-grow rounded-none border-gray-300 focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                  />
                </div>
              </div>

              <div className="col-span-3">
                <label htmlFor="about" className="block text-sm font-medium text-gray-700">
                  Event Message
                </label>
                <div className="mt-1">
                  <textarea
                    id="about"
                    name="about"
                    rows={3}
                    value={message} 
                    onChange={e => setMessage(e.target.value)}
                    className="mt-1 block w-full border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 sm:text-sm"
                    placeholder="you@example.com"
                    defaultValue={''}
                  />
                </div>
                <p className="mt-2 text-sm text-gray-500">
                  Brief description for your profile. URLs are hyperlinked.
                </p>
              </div>
            </div>
            <div className="bg-gray-50 px-4 py-3 text-right sm:px-6">
              <button
                type="submit"
                className="inline-flex justify-center border border-transparent bg-sky-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
              >
                Send Event
              </button>
            </div>
        </div>
      </div>
    </form>
  </div>
  )
}