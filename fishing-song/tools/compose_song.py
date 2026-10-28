"""Compose an original fishing song (instrumental + lyric timings).

Usage: python3 compose_song.py <out.wav> <lyrics.json> <film_seconds>
"""
import json
import sys
import wave

import numpy as np

SR = 44100
BPM = 84
BEAT = 60 / BPM
EIGHTH = BEAT / 2
BAR = BEAT * 4
rng = np.random.default_rng(7)

out_wav, out_json, film = sys.argv[1], sys.argv[2], float(sys.argv[3])
N = int(SR * (BAR * 5 + 2.0))
L = np.zeros(N)
R = np.zeros(N)


def hz(m):
    return 440.0 * 2 ** ((m - 69) / 12)


def add(sig, t, gain=1.0, pan=0.0):
    i = int(t * SR)
    if i >= N:
        return
    sig = sig[: N - i]
    L[i : i + len(sig)] += sig * gain * np.cos((pan + 1) * np.pi / 4)
    R[i : i + len(sig)] += sig * gain * np.sin((pan + 1) * np.pi / 4)


def pluck(m, dur=2.2, bright=0.5):
    """Karplus-Strong string, vectorised one period at a time."""
    p = max(2, int(round(SR / hz(m))))
    n = int(dur * SR)
    y = np.zeros(n + p)
    burst = rng.uniform(-1, 1, p)
    # soften the excitation for a warmer nylon-ish tone
    for _ in range(int((1 - bright) * 4)):
        burst = 0.5 * (burst + np.roll(burst, 1))
    burst -= burst.mean()
    y[:p] = burst
    decay = 0.996
    for k in range(1, (n + p) // p):
        a, b = k * p, min((k + 1) * p, n + p)
        prev = y[a - p : b - p]
        prev_shift = y[a - p - 1 : b - p - 1] if a - p - 1 >= 0 else np.concatenate(([0.0], prev[:-1]))
        y[a:b] = decay * 0.5 * (prev + prev_shift)
    y = y[p:]
    env = np.ones(n)
    rel = int(0.08 * SR)
    env[-rel:] = np.linspace(1, 0, rel)
    return y * env


def tone(m, dur, kind="whistle"):
    n = int(dur * SR)
    t = np.arange(n) / SR
    f = hz(m)
    if kind == "whistle":
        vib = 1 + 0.006 * np.sin(2 * np.pi * 5.4 * t) * np.clip((t - 0.15) / 0.2, 0, 1)
        ph = 2 * np.pi * np.cumsum(f * vib) / SR
        s = np.sin(ph) + 0.12 * np.sin(2 * ph) + 0.04 * np.sin(3 * ph)
        breath = np.convolve(rng.normal(0, 1, n), np.ones(30) / 30, "same") * 0.03
        s = s + breath
        att, rel = 0.035, min(0.18, dur * 0.4)
    elif kind == "bass":
        ph = 2 * np.pi * f * t
        s = np.sin(ph) + 0.25 * np.sin(2 * ph)
        s *= np.exp(-t * 2.2)
        att, rel = 0.01, 0.12
    else:  # pad
        ph = 2 * np.pi * f * t
        s = np.sin(ph) + 0.3 * np.sin(2 * ph + 0.3)
        att, rel = 0.6, 0.6
    env = np.clip(t / att, 0, 1) * np.clip((dur - t) / rel, 0, 1)
    return s * env


def shaker(dur=0.09, accent=1.0):
    n = int(dur * SR)
    s = rng.uniform(-1, 1, n)
    s = s - np.convolve(s, np.ones(6) / 6, "same")  # high-pass-ish
    t = np.arange(n) / SR
    return s * np.exp(-t * 45) * accent


VOICINGS = {
    "G": [43, 50, 55, 59, 62, 67],
    "D": [38, 45, 50, 57, 62, 66],
    "Em": [40, 47, 52, 55, 59, 64],
    "C": [36, 48, 52, 55, 60, 64],
}
CHORDS = [("G", "G"), ("G", "D"), ("Em", "C"), ("G", "D"), ("C", "G")]
PATTERN = [0, 3, 4, 5]  # string index per eighth within a half bar

for b, halves in enumerate(CHORDS):
    for h, name in enumerate(halves):
        v = VOICINGS[name]
        t0 = b * BAR + h * 2 * BEAT
        last = b == 4 and h == 1
        for e, s_idx in enumerate(PATTERN):
            m = v[s_idx] if s_idx else v[0] + 12
            human = rng.normal(0, 0.006)
            add(pluck(m, 3.0 if last else 2.0, 0.45), t0 + e * EIGHTH + human, 0.32 if e else 0.4, pan=-0.25)
            if last:
                break
        # bass on the half-bar downbeat
        add(tone(v[0], 1.3, "bass"), t0, 0.32)
        # soft pad under the lyric bars
        if b >= 1:
            for m in v[2:5]:
                add(tone(m + 12, 2 * BEAT + 0.4, "pad"), t0, 0.022, pan=0.3)
    if b >= 1:
        for e in range(8):
            if b == 4 and e > 3:
                break
            add(shaker(accent=1.0 if e % 2 else 0.55), b * BAR + e * EIGHTH + rng.normal(0, 0.004), 0.05, pan=0.35)

# Lyrics: (word, eighth offset in its bar, midi)
LINES = [
    [("Mist", 0, 74), ("on", 1, 71), ("the", 1.5, 71), ("river,", 2, 69),
     ("line", 4, 69), ("in", 5, 71), ("the", 5.5, 69), ("stream", 6, 66)],
    [("Cast", 0, 76), ("it", 1, 74), ("out", 1.5, 71), ("slow,", 2, 71),
     ("chasing", 4, 72), ("a", 5.5, 71), ("dream", 6, 67)],
    [("Waiting", 0, 74), ("for", 1, 71), ("the", 1.5, 74), ("tug", 2, 79),
     ("beneath", 4, 78), ("the", 5.5, 76), ("gray", 6, 74)],
    [("Let", 0, 76), ("the", 0.5, 74), ("river", 1, 72), ("keep", 2, 71),
     ("the", 3, 69), ("hours", 4, 71), ("away", 5.5, 67)],
]
# A whistled pickup in the intro bar
for off, m, d in [(4, 62, 0.5), (5, 67, 0.4), (6, 71, 0.9)]:
    add(tone(m, d, "whistle"), off * EIGHTH, 0.16, pan=0.1)

lyrics = []
for li, words in enumerate(LINES):
    bar_t = (li + 1) * BAR
    entries = []
    for wi, (w, off, m) in enumerate(words):
        start = bar_t + off * EIGHTH
        nxt = words[wi + 1][1] if wi + 1 < len(words) else 8 + (2 if li == 3 else 0)
        dur = min((nxt - off) * EIGHTH, 1.6)
        add(tone(m, dur * 0.95, "whistle"), start, 0.2, pan=0.1)
        entries.append({"id": f"l{li}w{wi}", "text": w, "start": round(start, 3), "end": round(start + dur, 3)})
    lyrics.append(entries)

# Room reverb: exponentially decaying noise IR, FFT convolution
ir_n = int(1.3 * SR)
ir_t = np.arange(ir_n) / SR
ir = rng.normal(0, 1, ir_n) * np.exp(-ir_t * 4.2)
ir[: int(0.012 * SR)] = 0
ir /= np.sqrt(np.sum(ir**2))


def reverb(x, wet=0.16):
    size = 1 << int(np.ceil(np.log2(len(x) + ir_n)))
    y = np.fft.irfft(np.fft.rfft(x, size) * np.fft.rfft(ir, size), size)[: len(x)]
    return x + wet * y


L, R = reverb(L), reverb(R)

# Trim to the film and fade out its last 0.7 s
n_out = int(film * SR)
L, R = L[:n_out], R[:n_out]
fade = int(0.7 * SR)
ramp = np.linspace(1, 0, fade) ** 1.5
L[-fade:] *= ramp
R[-fade:] *= ramp
fade_in = int(0.01 * SR)
L[:fade_in] *= np.linspace(0, 1, fade_in)
R[:fade_in] *= np.linspace(0, 1, fade_in)

peak = max(np.abs(L).max(), np.abs(R).max())
st = np.stack([L, R], 1) / peak * 0.89  # about -1 dBFS
pcm = (st * 32767).astype("<i2")
with wave.open(out_wav, "wb") as wf:
    wf.setnchannels(2)
    wf.setsampwidth(2)
    wf.setframerate(SR)
    wf.writeframes(pcm.tobytes())

json.dump({"bpm": BPM, "bar": BAR, "lines": lyrics}, open(out_json, "w"), indent=1)
print("wrote", out_wav, f"{film:.2f}s")
