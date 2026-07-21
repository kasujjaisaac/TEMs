<style>
    .hr-core,
    .hr-core * {
        box-sizing: border-box;
    }

    .hr-core {
        color: var(--onyx-text, #f5f7fa);
        display: grid;
        font-family: "Poppins", system-ui, -apple-system, "Segoe UI", sans-serif;
        font-size: 12px;
        gap: 14px;
        width: 100%;
    }

    .hr-core-header,
    .hr-core-card,
    .hr-core-panel {
        background:
            linear-gradient(180deg, rgba(255,255,255,.035), rgba(255,255,255,.012)),
            var(--onyx-surface, #101923);
        border: 1px solid rgba(255,106,0,.18);
        border-radius: 0;
        box-shadow: 0 10px 28px rgba(0,0,0,.22);
    }

    .hr-core-header {
        align-items: center;
        display: flex;
        gap: 14px;
        justify-content: space-between;
        padding: 16px;
    }

    .hr-core-title {
        align-items: center;
        display: flex;
        gap: 12px;
        min-width: 0;
    }

    .hr-core-icon {
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

    .hr-core h1,
    .hr-core h2,
    .hr-core h3 {
        color: #ffffff;
        margin: 0;
    }

    .hr-core h1 {
        font-size: 18px;
        font-weight: 900;
        line-height: 1.15;
    }

    .hr-core h2,
    .hr-core h3 {
        font-size: 13px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .hr-core-muted {
        color: var(--onyx-muted, #8d99a8);
        font-size: 11px;
        font-weight: 700;
        line-height: 1.45;
    }

    .hr-core-actions,
    .hr-core-filters {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .hr-core-button,
    .hr-core-icon-button {
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

    .hr-core-button.secondary,
    .hr-core-icon-button {
        background: rgba(255,255,255,.025);
        border-color: rgba(255,106,0,.2);
        color: var(--onyx-text, #f5f7fa);
    }

    .hr-core-button:hover,
    .hr-core-icon-button:hover {
        border-color: var(--onyx-accent-2, #ff8a1d);
        box-shadow: 0 0 0 3px rgba(255,106,0,.11);
        text-decoration: none;
    }

    .hr-core-icon-button {
        flex: 0 0 34px;
        padding: 0;
        width: 34px;
    }

    .hr-core-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .hr-core-card {
        padding: 13px;
    }

    .hr-core-card span,
    .hr-core-field label,
    .hr-core-table th {
        color: var(--onyx-muted, #8d99a8);
        display: block;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .5px;
        text-transform: uppercase;
    }

    .hr-core-card strong {
        color: #ffffff;
        display: block;
        font-size: 18px;
        font-weight: 900;
        margin-top: 6px;
        overflow-wrap: anywhere;
    }

    .hr-core-panel {
        padding: 15px;
    }

    .hr-core-panel-head {
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,.06);
        display: flex;
        gap: 10px;
        justify-content: space-between;
        margin-bottom: 13px;
        padding-bottom: 11px;
    }

    .hr-core-table-wrap {
        overflow-x: auto;
    }

    .hr-core-table {
        border-collapse: collapse;
        min-width: 820px;
        width: 100%;
    }

    .hr-core-table th,
    .hr-core-table td {
        border-bottom: 1px solid rgba(255,255,255,.06);
        font-size: 12px;
        padding: 10px;
        text-align: left;
        vertical-align: middle;
    }

    .hr-core-table tbody tr:hover {
        background: rgba(255,106,0,.055);
    }

    .hr-core-table-title {
        color: #ffffff;
        display: block;
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 3px;
    }

    .hr-core-badge {
        border: 1px solid rgba(255,106,0,.32);
        color: #d8d8de;
        display: inline-flex;
        font-size: 10px;
        font-weight: 900;
        padding: 5px 7px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .hr-core-badge.success { border-color: rgba(143,240,195,.32); color: #8ff0c3; }
    .hr-core-badge.warning { border-color: rgba(255,210,122,.32); color: #ffd27a; }

    .hr-core-form {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .hr-core-field {
        display: grid;
        gap: 6px;
        min-width: 0;
    }

    .hr-core-field.full { grid-column: 1 / -1; }
    .hr-core-field.double { grid-column: span 2; }

    .hr-core-field input,
    .hr-core-field select,
    .hr-core-field textarea {
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

    .hr-core-field textarea {
        min-height: 82px;
        resize: vertical;
    }

    .hr-core-field input:focus,
    .hr-core-field select:focus,
    .hr-core-field textarea:focus {
        border-color: var(--onyx-accent, #ff6a00);
        box-shadow: 0 0 0 3px rgba(255,106,0,.13);
        outline: 0;
    }

    .hr-core-field select option {
        background: #050506;
        color: #ffffff;
    }

    .hr-core-alert {
        background:
            linear-gradient(180deg, rgba(255,255,255,.035), rgba(255,255,255,.012)),
            var(--onyx-surface, #101923);
        border: 1px solid rgba(255,106,0,.18);
        color: #ffffff;
        font-size: 12px;
        font-weight: 800;
        padding: 10px 12px;
    }

    .hr-core-alert.success { border-color: rgba(143,240,195,.28); color: #8ff0c3; }
    .hr-core-alert.error { border-color: rgba(255,138,138,.28); color: #ff8a8a; }

    .hr-core-split {
        display: grid;
        gap: 12px;
        grid-template-columns: minmax(0, 1.15fr) minmax(280px, .85fr);
    }

    @media (max-width: 1180px) {
        .hr-core-grid,
        .hr-core-form,
        .hr-core-split {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 720px) {
        .hr-core-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .hr-core-grid,
        .hr-core-form,
        .hr-core-split {
            grid-template-columns: 1fr;
        }

        .hr-core-field.double {
            grid-column: 1;
        }

        .hr-core-button {
            width: 100%;
        }
    }

    .hr-core{gap:10px}
    .hr-core-header{align-items:center!important;gap:8px!important;min-height:0!important;padding:8px 10px!important}
    .hr-core-title{gap:8px!important}
    .hr-core-icon{flex:0 0 28px!important;font-size:10px!important;height:28px!important;width:28px!important}
    .hr-core h1{font-size:14px!important;font-weight:800!important;line-height:1.1!important}
    .hr-core-muted{font-size:10px!important;font-weight:400!important;line-height:1.25!important}
    .hr-core-button,.hr-core-icon-button{font-size:10px!important;font-weight:700!important;gap:6px!important;min-height:30px!important;padding:0 9px!important}
    .hr-core-panel,.hr-core-card{padding:10px!important}
    .hr-core-panel-head{margin-bottom:9px!important;padding-bottom:8px!important}
    .hr-core h2,.hr-core h3{font-size:11px!important;font-weight:700!important}
    .hr-core-card strong{font-size:14px!important;font-weight:700!important;margin-top:3px!important}
</style>
