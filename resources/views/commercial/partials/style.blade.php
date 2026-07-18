<style>
    .commercial-page,
    .commercial-page * {
        box-sizing: border-box;
    }

    .commercial-page {
        color: var(--onyx-text, #f5f7fa);
        display: grid;
        font-family: "Poppins", system-ui, -apple-system, "Segoe UI", sans-serif;
        font-size: 12px;
        gap: 14px;
        width: 100%;
    }

    .commercial-header,
    .commercial-card,
    .commercial-panel {
        background:
            linear-gradient(180deg, rgba(255,255,255,.035), rgba(255,255,255,.012)),
            var(--onyx-surface, #101923);
        border: 1px solid rgba(255,106,0,.18);
        border-radius: 0;
        box-shadow: 0 10px 28px rgba(0,0,0,.22);
    }

    .commercial-header {
        align-items: center;
        display: flex;
        gap: 14px;
        justify-content: space-between;
        padding: 16px;
    }

    .commercial-title {
        align-items: center;
        display: flex;
        gap: 12px;
        min-width: 0;
    }

    .commercial-title-icon {
        align-items: center;
        background: var(--onyx-accent, #ff6a00);
        border: 1px solid var(--onyx-accent, #ff6a00);
        color: #050506;
        display: inline-flex;
        flex: 0 0 38px;
        height: 38px;
        justify-content: center;
        width: 38px;
    }

    .commercial-header h1 {
        color: #ffffff;
        font-size: 18px;
        font-weight: 900;
        line-height: 1.15;
        margin: 0 0 3px;
    }

    .commercial-muted {
        color: var(--onyx-muted, #8d99a8);
        font-size: 11px;
        font-weight: 700;
        line-height: 1.45;
    }

    .commercial-actions,
    .commercial-filters {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .commercial-button,
    .commercial-icon-button {
        align-items: center;
        background: var(--onyx-accent, #ff6a00);
        border: 1px solid var(--onyx-accent, #ff6a00);
        color: #050506;
        cursor: pointer;
        display: inline-flex;
        font: inherit;
        font-size: 11px;
        font-weight: 900;
        gap: 8px;
        justify-content: center;
        min-height: 36px;
        padding: 0 12px;
        text-decoration: none;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .commercial-button.secondary,
    .commercial-icon-button {
        background: rgba(255,255,255,.025);
        border-color: rgba(255,106,0,.2);
        color: var(--onyx-text, #f5f7fa);
    }

    .commercial-button:hover,
    .commercial-icon-button:hover {
        border-color: var(--onyx-accent-2, #ff8a1d);
        box-shadow: 0 0 0 3px rgba(255,106,0,.11);
        color: #050506;
        text-decoration: none;
    }

    .commercial-button.secondary:hover,
    .commercial-icon-button:hover {
        background: rgba(255,106,0,.1);
        color: #ffffff;
    }

    .commercial-icon-button {
        flex: 0 0 34px;
        padding: 0;
        width: 34px;
    }

    .commercial-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .commercial-card {
        min-width: 0;
        padding: 13px;
    }

    .commercial-card span,
    .commercial-field label,
    .commercial-table th {
        color: var(--onyx-muted, #8d99a8);
        display: block;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .5px;
        text-transform: uppercase;
    }

    .commercial-card strong {
        color: #ffffff;
        display: block;
        font-size: 18px;
        font-weight: 900;
        margin-top: 6px;
        overflow-wrap: anywhere;
    }

    .commercial-panel {
        padding: 15px;
    }

    .commercial-panel-head {
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,.06);
        display: flex;
        gap: 10px;
        justify-content: space-between;
        margin-bottom: 13px;
        padding-bottom: 11px;
    }

    .commercial-panel h2,
    .commercial-panel h3 {
        color: #ffffff;
        font-size: 13px;
        font-weight: 900;
        margin: 0;
        text-transform: uppercase;
    }

    .commercial-table-wrap {
        overflow-x: auto;
    }

    .commercial-table {
        border-collapse: collapse;
        min-width: 820px;
        width: 100%;
    }

    .commercial-table th,
    .commercial-table td {
        border-bottom: 1px solid rgba(255,255,255,.06);
        font-size: 12px;
        padding: 10px;
        text-align: left;
        vertical-align: middle;
    }

    .commercial-table td {
        color: var(--onyx-text, #f5f7fa);
        font-weight: 650;
    }

    .commercial-table tbody tr:hover {
        background: rgba(255,106,0,.055);
    }

    .commercial-table-title {
        color: #ffffff;
        display: block;
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 3px;
    }

    .commercial-badge {
        border: 1px solid rgba(255,106,0,.32);
        color: #d8d8de;
        display: inline-flex;
        font-size: 10px;
        font-weight: 900;
        padding: 5px 7px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .commercial-badge.success { border-color: rgba(143,240,195,.32); color: #8ff0c3; }
    .commercial-badge.warning { border-color: rgba(255,210,122,.32); color: #ffd27a; }
    .commercial-badge.danger { border-color: rgba(255,138,138,.32); color: #ff8a8a; }

    .commercial-form {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .commercial-field {
        display: grid;
        gap: 6px;
        min-width: 0;
    }

    .commercial-field.full { grid-column: 1 / -1; }
    .commercial-field.double { grid-column: span 2; }

    .commercial-field input,
    .commercial-field select,
    .commercial-field textarea {
        background: #0b141e;
        border: 1px solid rgba(255,106,0,.18);
        color: #ffffff;
        font: inherit;
        font-size: 12px;
        font-weight: 700;
        min-height: 38px;
        padding: 8px 10px;
        width: 100%;
    }

    .commercial-field textarea {
        min-height: 82px;
        resize: vertical;
    }

    .commercial-field input:focus,
    .commercial-field select:focus,
    .commercial-field textarea:focus {
        border-color: var(--onyx-accent, #ff6a00);
        box-shadow: 0 0 0 3px rgba(255,106,0,.13);
        outline: 0;
    }

    .commercial-field select option {
        background: #050506;
        color: #ffffff;
    }

    .commercial-alert {
        background:
            linear-gradient(180deg, rgba(255,255,255,.035), rgba(255,255,255,.012)),
            var(--onyx-surface, #101923);
        border: 1px solid rgba(255,106,0,.18);
        color: #ffffff;
        font-size: 12px;
        font-weight: 800;
        padding: 10px 12px;
    }

    .commercial-alert.success { border-color: rgba(143,240,195,.28); color: #8ff0c3; }
    .commercial-alert.error { border-color: rgba(255,138,138,.28); color: #ff8a8a; }

    .commercial-split {
        display: grid;
        gap: 12px;
        grid-template-columns: minmax(0, 1.2fr) minmax(280px, .8fr);
    }

    @media (max-width: 1180px) {
        .commercial-grid,
        .commercial-form,
        .commercial-split {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 720px) {
        .commercial-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .commercial-grid,
        .commercial-form,
        .commercial-split {
            grid-template-columns: 1fr;
        }

        .commercial-field.double {
            grid-column: 1;
        }

        .commercial-button {
            width: 100%;
        }

        .commercial-actions,
        .commercial-filters {
            align-items: stretch;
            width: 100%;
        }

        .commercial-actions > *,
        .commercial-filters > * {
            flex: 1 1 100%;
        }

        .commercial-icon-button {
            flex-basis: 40px;
            height: 40px;
            width: 40px;
        }

        .commercial-table-wrap {
            max-width: 100%;
            overflow: visible;
        }

        .commercial-table,
        .commercial-table thead,
        .commercial-table tbody,
        .commercial-table tr,
        .commercial-table th,
        .commercial-table td {
            display: block;
            width: 100%;
        }

        .commercial-table {
            min-width: 0;
        }

        .commercial-table thead {
            display: none;
        }

        .commercial-table tbody {
            display: grid;
            gap: 9px;
        }

        .commercial-table tr {
            background:
                linear-gradient(180deg, rgba(255,255,255,.035), rgba(255,255,255,.012)),
                var(--onyx-surface, #101923);
            border: 1px solid rgba(255,106,0,.18);
            padding: 8px;
        }

        .commercial-table td {
            align-items: start;
            border: 0;
            display: grid;
            gap: 8px;
            grid-template-columns: minmax(88px, 34%) minmax(0, 1fr);
            padding: 6px 0;
            text-align: right;
        }

        .commercial-table td::before {
            color: var(--onyx-muted, #8d99a8);
            content: attr(data-label);
            font-size: 8px;
            font-weight: 900;
            text-align: left;
            text-transform: uppercase;
        }
    }
</style>
