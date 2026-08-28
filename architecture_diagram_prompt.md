# WeatherGPT — Architecture Diagram: AI Image-Generation Prompt

**Where this goes:** Slide 5 — "Section 04 · Technical Approach" — of `WeatherGPT_SIH_PPT.pptx`,
replacing the dashed image placeholder on the left ~55% of the slide.

**Target size:** 16:9, minimum **2400 × 1350 px** (so it stays sharp on a large projector screen).
Export as PNG with a **white or transparent background** — the placeholder area is `F3F4F8` (very
light gray), so a pure white diagram background will blend in almost seamlessly.

**Placement box in the slide:** roughly 7.4in × 5.4in (landscape, slightly taller than 16:9 — a plain
16:9 image will letterbox slightly, which is fine).

---

## Primary prompt (copy-paste into your image generator)

> Design a clean, professional **software system architecture diagram** for a government
> technology / disaster-management presentation, in a **flat vector infographic style**
> (no photorealism, no 3D, no gradients-heavy skeuomorphism). The diagram shows nine
> stacked horizontal layers connected top-to-bottom by downward arrows, each layer a
> rounded rectangle, plus one side branch. Use this **exact color palette**: deep navy
> blue `#1E2555` for layer boxes, warm gold `#D4A94A` for arrows and the final highlighted
> layer, maroon `#7A2333` as a small accent color for a title bar, white `#FFFFFF`
> background, and white/light-ice-blue text on the navy boxes. Typography should be a
> classic serif (Times New Roman / Georgia style), bold for layer titles, italic for
> the small subtitle under each title. No photographic textures, no drop shadows heavier
> than a subtle 2px, no cartoon mascots, no human figures, no logos, no watermarks.
>
> **Layer 1 (top): "USER"** — subtitle "Text · Voice · Location". A plain white/light-gray
> box (not navy), to visually mark the entry point.
>
> **Layer 2: "MOBILE & WEB"** — subtitle "Flutter Mobile App → WeatherGPT Web App (HTML · CSS · JavaScript)".
>
> **Layer 3: "BACKEND"** — subtitle "Flask (Python) · REST APIs · WebSockets · Gunicorn / Nginx".
>
> **Layer 4: "CONVERSATIONAL AI LAYER"** — subtitle "LLM · RAG · Tool / Function Calling · Intent & Context Management".
>
> **Layer 5: "WEATHER INTELLIGENCE ENGINE"** — subtitle "Forecast · Alert · GIS/Risk · Climate · Advisory · Evidence · Orchestration". Make this box visually slightly larger/taller than the others, or give it a subtle inner border, since it is the conceptual centerpiece of the whole system.
>
> **Layer 6: "METEOROLOGICAL DATA SOURCES"** — subtitle "IMD · WMO WIS2/MQTT · GFS (NOAA) · WRF (NCAR) · MOSDAC (ISRO) · Radar · Government Bulletins".
>
> **Layer 7: "DATA & STORAGE LAYER"** — subtitle "PostgreSQL · PostGIS · TimescaleDB · Redis · pgvector · MinIO/S3".
>
> **Layer 8: "GROUNDED RESPONSE ENGINE"** — subtitle "Evidence + Source + Timestamp + Forecast Horizon + Location". Give this box a slightly different shade of navy (a touch darker/richer) to mark it as a distinct sub-system.
>
> **Layer 9 (bottom): "USER RESPONSE"** — subtitle "Text · Map Visualization · Voice · Alert / Notification". This final layer should be filled in the **gold accent color** (not navy), with dark navy text, to visually mark it as the final output — a clear "destination" color distinct from every layer above it.
>
> Connect each layer to the next with a **bold, simple downward-pointing triangular arrow** in the gold accent color, evenly spaced with generous whitespace between layers (this is important — the layers must **not** look cramped; leave roughly 20% of each layer's height as clear gap before the next arrow).
>
> To the **right of layers 2–4**, add a small side branch/inset diagram labeled **"VOICE & MULTILINGUAL PIPELINE (BHASHINI)"**: five small horizontal boxes connected left-to-right by thin gold arrows, reading "Voice Input" → "ASR + Language ID" → "WeatherGPT Core" → "Translate (BHASHINI)" → "TTS Response". Style these boxes the same navy/white as the main stack, just smaller.
>
> Overall composition: portrait-leaning vertical flow on the left ~65% of the canvas, the voice-pipeline inset in the top-right ~35%, with generous white margin around the whole diagram (at least 5% padding on every edge). The diagram must communicate **at a glance, from across a room**, so keep line weights bold (3–4px), text high-contrast, and avoid any small decorative clutter. No background texture, no vignette, no watermark, no signature, no border frame around the whole canvas.

---

## Alternate, shorter prompt (for simpler / token-limited image models)

> Flat vector infographic, 16:9, white background. Nine stacked navy-blue (#1E2555) rounded
> rectangle boxes connected by bold gold (#D4A94A) downward arrows, labeled top to bottom:
> USER → MOBILE & WEB (Flutter/HTML-CSS-JS) → BACKEND (Flask/Python) → CONVERSATIONAL AI LAYER
> (LLM/RAG/Tool-Calling) → WEATHER INTELLIGENCE ENGINE (Forecast/Alert/GIS/Climate/Advisory/Evidence)
> → METEOROLOGICAL DATA SOURCES (IMD/WIS2/GFS/WRF/MOSDAC/Radar) → DATA & STORAGE LAYER
> (PostgreSQL/PostGIS/TimescaleDB/Redis/pgvector/MinIO) → GROUNDED RESPONSE ENGINE (Evidence+Source+Time+Horizon)
> → USER RESPONSE (gold-filled box, Text/Map/Voice/Alert). Bold serif labels, white text on navy
> boxes, generous spacing between layers, clean government-technology aesthetic, no logos, no
> people, no photorealism, no watermark.

---

## Notes for whoever generates this

- Keep every layer's **label text identical** to what is listed above — the deck's speaker notes
  and adjacent text (voice pipeline, "how WeatherGPT stays grounded") reference these exact names.
- If your image tool renders illegible small text inside the boxes, it's fine to generate the
  diagram with placeholder/blurred label text and have PowerPoint's own text boxes overlay the
  real labels on top of the image — but the simplest path is only using this image for the
  outer shapes/arrows/color blocking, then re-adding the 9 labels as native PowerPoint text boxes
  over it for guaranteed crispness on the projector.
- Once generated, insert the image into the dashed placeholder box on **Slide 5** and delete the
  placeholder shapes/text (the icon, "INSERT ARCHITECTURE DIAGRAM HERE" label, and the reference
  layer list) — they exist only as a stand-in until the real diagram is pasted in.
