// Small CSV reader/writer shared by the browser UI and the Node CLI.
(function (root) {
  'use strict';

  function detectDelimiter(text) {
    const first = text.split(/\r?\n/, 1)[0] || '';
    const counts = [',', ';', '\t', '|'].map((d) => [d, first.split(d).length]);
    counts.sort((a, b) => b[1] - a[1]);
    return counts[0][1] > 1 ? counts[0][0] : ',';
  }

  function parseCSV(text, delimiter) {
    text = String(text).replace(/^﻿/, '');
    const d = delimiter || detectDelimiter(text);
    const rows = [];
    let row = [];
    let field = '';
    let quoted = false;
    for (let i = 0; i < text.length; i++) {
      const c = text[i];
      if (quoted) {
        if (c === '"') {
          if (text[i + 1] === '"') { field += '"'; i++; } else quoted = false;
        } else field += c;
      } else if (c === '"' && field === '') quoted = true;
      else if (c === d) { row.push(field); field = ''; }
      else if (c === '\n' || c === '\r') {
        if (c === '\r' && text[i + 1] === '\n') i++;
        row.push(field); field = '';
        if (row.length > 1 || row[0] !== '') rows.push(row);
        row = [];
      } else field += c;
    }
    row.push(field);
    if (row.length > 1 || row[0] !== '') rows.push(row);
    return rows;
  }

  function toCSV(rows) {
    return rows.map((r) => r.map((v) => {
      const s = v == null ? '' : String(v);
      return /[",\r\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
    }).join(',')).join('\r\n') + '\r\n';
  }

  const EMAIL_LIKE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  /** Returns { header: string[]|null, rows: string[][], column: number } */
  function findEmailColumn(rows) {
    if (!rows.length) return { header: null, rows: [], column: -1 };
    const first = rows[0].map((h) => h.trim().toLowerCase());
    const named = first.findIndex((h) => /^(e-?mail|email ?address|e-mail address|work email|business email|mail)$/.test(h));
    const hasHeader = named !== -1 || !rows[0].some((v) => EMAIL_LIKE.test(v.trim()));
    const body = hasHeader ? rows.slice(1) : rows;
    let column = named;
    if (column === -1) {
      const width = Math.max(...rows.map((r) => r.length));
      let best = 0;
      for (let c = 0; c < width; c++) {
        const hits = body.slice(0, 200).filter((r) => EMAIL_LIKE.test((r[c] || '').trim())).length;
        if (hits > best) { best = hits; column = c; }
      }
    }
    return { header: hasHeader ? rows[0] : null, rows: body, column };
  }

  /** Pull addresses out of free text (one per line, or comma/space separated). */
  function extractEmails(text) {
    return String(text).split(/[\s,;]+/).map((s) => s.trim().replace(/^["'<(]+|[">)'.]+$/g, '')).filter((s) => s.includes('@'));
  }

  const api = { parseCSV, toCSV, findEmailColumn, extractEmails, detectDelimiter };
  if (typeof module !== 'undefined' && module.exports) module.exports = api;
  else root.CSV = api;
})(typeof window !== 'undefined' ? window : globalThis);
