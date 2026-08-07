# UperLevel Capture Agent

Hostname-mapped desktop screen-capture agent. Runs with a visible tray icon at all times — this is by design, not an oversight (see `agent.js`'s header comment).

## One-time setup (per office PC)

1. `npm install` inside this folder.
2. `copy .env.example .env` (Windows) and fill in `AGENT_SECRET` — must match `AGENT_SHARED_SECRET` in the Laravel app's `.env`.
3. Find this PC's hostname: open Command Prompt and run `hostname`.
4. In UperLevel: **HR → Employees → [this employee] → Edit → Workstation machine name** — enter the exact hostname from step 3.
5. In UperLevel: **Company → Settings → Screen Monitoring** — enable monitoring and set the interval (should match `CAPTURE_INTERVAL_MINUTES` below).
6. `npm start` to confirm it runs and the tray icon appears. Check `Company → User Working` in UperLevel a few seconds later — a capture should appear.
7. To run automatically on boot: press `Win+R`, type `shell:startup`, and drop a shortcut to `npm start` (or a packaged `.exe` if you've bundled this with `pkg`/`electron-builder`) into that folder.

## Files

- `agent.js` — the agent itself.
- `.env` — per-machine config (never commit this; it holds the shared secret).
- `assets/icon.png` — tray icon; replace with your own branding if you like.
- `agent.log` — local error/activity log, also viewable from the tray menu.

## Notes

- `API_URL` is hardcoded to `http://127.0.0.1:8000` by default for local dev — change it in `.env` before deploying to a real office network.
- If the tray icon fails to load on a given machine (missing native dependency), the agent still runs and keeps capturing — it just falls back to a visible console window instead of disappearing. Check `agent.log` if that happens.
