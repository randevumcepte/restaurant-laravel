'use strict';
const cfg = require('./config');
const RANK = { error: 0, warn: 1, info: 2, debug: 3 };
const lvl = RANK[cfg.logLevel] ?? 2;

function stamp() {
  // Not: ortam saatine gore; sadece log icin
  return new Date().toISOString().replace('T', ' ').slice(0, 19);
}
function line(level, args) {
  if ((RANK[level] ?? 2) > lvl) return;
  const fn = level === 'error' ? console.error : level === 'warn' ? console.warn : console.log;
  fn(`[${stamp()}] ${level.toUpperCase().padEnd(5)}`, ...args);
}
module.exports = {
  error: (...a) => line('error', a),
  warn: (...a) => line('warn', a),
  info: (...a) => line('info', a),
  debug: (...a) => line('debug', a),
};
