# Audio Assets for Waiter Dashboard

## beep.mp3

Generate a 800Hz, 500ms beep sound for order alerts.

### Generate using ffmpeg:
```bash
ffmpeg -f lavfi -i "sine=frequency=800:duration=0.5" -y beep.mp3
```

### Or using sox:
```bash
sox -n -r 44100 beep.wav synth 0.5 sine 800
lame beep.mp3 beep.mp3
rm beep.wav
```

The audio file is used by the waiter dashboard to alert staff when new orders are ready for delivery.
The sound will only play after the first user interaction (browser policy requirement).
