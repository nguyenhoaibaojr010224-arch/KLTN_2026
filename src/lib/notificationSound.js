let audioContext = null;
let unlockRegistered = false;
let unlocked = false;
let lastPlayedAt = 0;
let pendingNotificationSound = false;
let knownSupportNotificationKeys = new Set();
let knownInvoiceNotificationKeys = new Set();

function getAudioContext() {
  if (typeof window === "undefined") {
    return null;
  }

  const AudioContextCtor = window.AudioContext || window.webkitAudioContext;

  if (!AudioContextCtor) {
    return null;
  }

  if (!audioContext) {
    audioContext = new AudioContextCtor();
  }

  return audioContext;
}

async function unlockAudioContext() {
  const context = getAudioContext();

  if (!context) {
    return false;
  }

  try {
    if (context.state === "suspended") {
      await context.resume();
    }

    unlocked = context.state === "running";
    return unlocked;
  } catch {
    return false;
  }
}

export function installNotificationSoundUnlocker() {
  if (typeof window === "undefined" || unlockRegistered) {
    return;
  }

  unlockRegistered = true;

  const handleFirstInteraction = async () => {
    const hadPendingSound = pendingNotificationSound;

    if (await unlockAudioContext()) {
      pendingNotificationSound = false;

      if (hadPendingSound) {
        playSupportNotificationSound();
      }
    }
  };

  window.addEventListener("pointerdown", handleFirstInteraction, {
    passive: true,
  });
  window.addEventListener("click", handleFirstInteraction, {
    passive: true,
  });
  window.addEventListener("touchstart", handleFirstInteraction, {
    passive: true,
  });
  window.addEventListener("keydown", handleFirstInteraction);
}

function playTone(context, startTime, frequency, duration, volume) {
  const oscillator = context.createOscillator();
  const gainNode = context.createGain();

  oscillator.type = "sine";
  oscillator.frequency.setValueAtTime(frequency, startTime);
  gainNode.gain.setValueAtTime(0.0001, startTime);
  gainNode.gain.exponentialRampToValueAtTime(volume, startTime + 0.015);
  gainNode.gain.exponentialRampToValueAtTime(0.0001, startTime + duration);
  oscillator.connect(gainNode);
  gainNode.connect(context.destination);
  oscillator.start(startTime);
  oscillator.stop(startTime + duration + 0.02);
}

export async function playSupportNotificationSound() {
  const now = Date.now();

  if (now - lastPlayedAt < 1800) {
    return;
  }

  const context = getAudioContext();

  if (!context || (!unlocked && !(await unlockAudioContext()))) {
    pendingNotificationSound = true;
    return;
  }

  pendingNotificationSound = false;
  lastPlayedAt = now;
  const startTime = context.currentTime + 0.02;
  playTone(context, startTime, 880, 0.18, 0.28);
  playTone(context, startTime + 0.22, 1175, 0.2, 0.26);
}

export function rememberSupportNotificationKeys(keys = []) {
  knownSupportNotificationKeys = new Set(keys.map(String));
}

export function playSupportNotificationSoundForKeys(keys = [], { force = false } = {}) {
  const normalizedKeys = keys.map(String).filter(Boolean);
  const hasNewKey = normalizedKeys.some((key) => !knownSupportNotificationKeys.has(key));

  knownSupportNotificationKeys = new Set([
    ...knownSupportNotificationKeys,
    ...normalizedKeys,
  ]);

  if ((hasNewKey || force) && normalizedKeys.length) {
    playSupportNotificationSound();
  }
}

export function rememberInvoiceNotificationKeys(keys = []) {
  knownInvoiceNotificationKeys = new Set(keys.map(String));
}

export function playInvoiceNotificationSoundForKeys(keys = [], { force = false } = {}) {
  const normalizedKeys = keys.map(String).filter(Boolean);
  const hasNewKey = normalizedKeys.some((key) => !knownInvoiceNotificationKeys.has(key));

  knownInvoiceNotificationKeys = new Set([
    ...knownInvoiceNotificationKeys,
    ...normalizedKeys,
  ]);

  if ((hasNewKey || force) && normalizedKeys.length) {
    playSupportNotificationSound();
  }
}
