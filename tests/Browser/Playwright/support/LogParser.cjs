const fs = require('fs');
const path = require('path');

/**
 * Helper untuk baca & extract URL reset dari storage/logs/laravel.log.
 *
 * Test flow:
 *  1. LogParser.truncate() — kosongkan log sebelum test
 *  2. User submit form forgot-password → Laravel kirim email via log mailer
 *  3. LogParser.findResetUrl(portal) — extract URL dari log dengan regex
 *  4. Test visit URL → submit password baru → verify
 *
 * Log format (Monolog default):
 *   [2026-06-13 12:00:00] local.DEBUG: Email {...payload...}
 *   atau HTML body berisi URL
 */
class LogParser {
    constructor(logPath = 'C:\\laragon\\www\\erp-rt-rw-net\\storage\\logs\\laravel.log') {
        this.logPath = logPath;
    }

    /**
     * Truncate log file (kosongkan). Panggil sebelum setiap phase test.
     */
    truncate() {
        if (fs.existsSync(this.logPath)) {
            fs.writeFileSync(this.logPath, '');
        }
    }

    /**
     * Read log content.
     */
    read() {
        if (!fs.existsSync(this.logPath)) return '';
        return fs.readFileSync(this.logPath, 'utf8');
    }

    /**
     * Extract URL reset password dari log content.
     *
     * @param {string} portal - salah satu 'operator-saas', 'perusahaan', 'karyawan', 'pelanggan'
     * @returns {string|null} URL lengkap atau null kalau tidak ketemu
     */
    findResetUrl(portal) {
        const log = this.read();
        // Pattern: URL dengan ?token=...&email=... (+ optional company_id)
        // Exclude whitespace, quotes, angle brackets (HTML tags)
        const urlPattern = new RegExp(
            "https?://[^\\s\"'<>]*?/lupa-password-" + portal + "\\?[^\\s\"'<>]+",
            'g'
        );
        const matches = log.match(urlPattern);
        if (!matches || matches.length === 0) return null;
        // Decode HTML entities (&amp; → &) untuk href attribute, dan %3C/%3E (encoded < >)
        return matches[matches.length - 1]
            .replace(/&amp;/g, '&')
            .replace(/%3C/g, '<')
            .replace(/%3E/g, '>')
            .replace(/&lt;/g, '<')
            .replace(/&gt;/g, '>');
    }

    /**
     * Extract token dari URL reset.
     */
    extractToken(url) {
        if (!url) return null;
        const match = url.match(/[?&]token=([^&]+)/);
        return match ? match[1] : null;
    }

    /**
     * Extract email dari URL reset.
     */
    extractEmail(url) {
        if (!url) return null;
        const match = url.match(/[?&]email=([^&]+)/);
        return match ? decodeURIComponent(match[1]) : null;
    }

    /**
     * Extract company_id dari URL reset.
     */
    extractCompanyId(url) {
        if (!url) return null;
        const match = url.match(/[?&]company_id=([^&]+)/);
        return match ? decodeURIComponent(match[1]) : null;
    }
}

module.exports = LogParser;
