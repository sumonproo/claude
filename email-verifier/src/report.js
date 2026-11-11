'use strict';
// Columns appended to each row when results are exported as CSV.
const RESULT_COLUMNS = ['status', 'reason', 'score', 'suggestion', 'catch_all', 'role', 'free', 'disposable', 'mx', 'smtp_code', 'smtp_message'];

function resultCells(r) {
  if (!r) return RESULT_COLUMNS.map(() => '');
  return [
    r.status, r.reason, r.score, r.suggestion || '',
    r.catchAll == null ? '' : r.catchAll, r.role, r.free, r.disposable,
    (r.mx || [])[0] || '', r.smtp?.code || '', r.smtp?.message || r.smtp?.error || '',
  ];
}

module.exports = { RESULT_COLUMNS, resultCells };
