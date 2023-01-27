import Head from 'next/head'
import Image from 'next/image'
import styles from '../styles/Home.module.css'
import { useState, useEffect } from 'react'
import useSWR from 'swr';
import Pusher from 'pusher-js';
import { getURL } from '../utils/helpers';

export default function Home() {
  const [data, setData] = useState(null)
  const [isLoading, setLoading] = useState(false)
  const [username, setUsername] = useState('username');
  const [messages, setMessages] = useState([]);
  const [message, setMessage] = useState('');
  let allMessages = [];
  var channel = pusher.subscribe('partymechanics');
  channel.bind('connected', function(data) {
    alert(JSON.stringify(data));
  });
    useEffect(() => {
      setLoading(true)
      fetch('https://mint.ideafablabs.com/index.php/wp-json/mint/v1/users')
        .then((res) => res.json())
        .then((data) => {
          setData(data)
          setLoading(false)
        })
        Pusher.logToConsole = true;

        const pusher = new Pusher('01a52d68bccce5e260bd', {
            cluster: 'us3'
        });

        const channel = pusher.subscribe('partymechanics');
        channel.bind('esp-test-event', function (data) {
            allMessages.push(data);
            setMessages(allMessages);
        });
    }, [])

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

  if (isLoading) return <p>Loading...</p>
  if (!data) return <p>No profile data</p>

  return (
    <div className={styles.container}>
      <Head>
        <title>Mint Live NFC Readers</title>
        <meta name="description" content="Generated by create next app" />
        <link rel="icon" href="/favicon.ico" />
      </Head>

      <main className={styles.main}>
        <h1 className={styles.title}>
          Mint Websocket Connections
        </h1>
        <div className="d-flex flex-column align-items-stretch flex-shrink-0 bg-white">
                <div
                    className="d-flex align-items-center flex-shrink-0 p-3 link-dark text-decoration-none border-bottom">
                    <input className="fs-5 fw-semibold" value={username} onChange={e => setUsername(e.target.value)}/>
                </div>
                <div className="list-group list-group-flush border-bottom scrollarea">
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
                </div>
            </div>

            <form onSubmit={submit}>
                <input className="form-control" placeholder="Write a message" value={message}
                       onChange={e => setMessage(e.target.value)}
                />
            </form>

        <p className={styles.description}>
          IP address currently connected to websocket:{' '}
          <code className={styles.code}>IP / ID</code>
        </p>
        <div>
          {data}
            {/* {data.map(user=>(
              <code className={styles.code}> {post.title} by {post.author}</code>
            ))} */}
        </div>

        <div className={styles.grid}>
          <a href="https://nextjs.org/docs" className={styles.card}>
            <h2>Documentation &rarr;</h2>
            <p>Find in-depth information about Next.js features and API.</p>
          </a>

          <a href="https://nextjs.org/learn" className={styles.card}>
            <h2>Learn &rarr;</h2>
            <p>Learn about Next.js in an interactive course with quizzes!</p>
          </a>

          <a
            href="https://github.com/vercel/next.js/tree/canary/examples"
            className={styles.card}
          >
            <h2>Examples &rarr;</h2>
            <p>Discover and deploy boilerplate example Next.js projects.</p>
          </a>

          <a
            href="https://vercel.com/new?utm_source=create-next-app&utm_medium=default-template&utm_campaign=create-next-app"
            target="_blank"
            rel="noopener noreferrer"
            className={styles.card}
          >
            <h2>Deploy &rarr;</h2>
            <p>
              Instantly deploy your Next.js site to a public URL with Vercel.
            </p>
          </a>
        </div>
      </main>

      <footer className={styles.footer}>
        <a
          href="https://vercel.com?utm_source=create-next-app&utm_medium=default-template&utm_campaign=create-next-app"
          target="_blank"
          rel="noopener noreferrer"
        >
          Idea Fab Labs{' '}
          <span className={styles.logo}>
            <Image src="/vercel.svg" alt="Vercel Logo" width={72} height={16} />
          </span>
        </a>
      </footer>
    </div>
  )
}
