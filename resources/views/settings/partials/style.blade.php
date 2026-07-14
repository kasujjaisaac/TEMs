<style>
    .access-page,
    .access-page * {
        border-radius: 0 !important;
        box-sizing: border-box;
    }

    .access-page {
        align-content: start;
        color: #ffffff;
        display: grid;
        font-family: "Poppins", system-ui, -apple-system, "Segoe UI", sans-serif;
        font-size: 12px;
        gap: 18px;
        grid-auto-rows: max-content;
        width: 100%;
    }

    .access-header,
    .access-panel,
    .access-kpi,
    .permission-group {
        background: var(--onyx-surface, #1c1c27);
        border: 1px solid var(--onyx-border, #2a2a3b);
    }

    .access-header {
        align-items: flex-start;
        display: flex;
        gap: 14px;
        justify-content: space-between;
        padding: 18px;
    }

    .access-header-compact {
        align-items: center;
        align-self: start;
        height: auto;
        min-height: auto;
        padding: 12px 16px;
    }

    .access-title {
        align-items: center;
        display: flex;
        gap: 12px;
        min-width: 0;
    }

    .access-title-icon {
        align-items: center;
        background: #ffffff;
        color: #050506;
        display: inline-flex;
        flex: 0 0 42px;
        height: 42px;
        justify-content: center;
        width: 42px;
    }

    .access-header-compact .access-title-icon {
        flex-basis: 34px;
        height: 34px;
        width: 34px;
    }

    .access-header h1 {
        color: #ffffff;
        font-size: 20px;
        font-weight: 900;
        line-height: 1.15;
        margin: 0 0 6px;
    }

    .access-header-compact h1 {
        font-size: 18px;
        margin-bottom: 2px;
    }

    .access-header-compact p {
        font-size: 11px;
        line-height: 1.35;
    }

    .access-header-compact .access-button {
        min-height: 34px;
        padding: 0 11px;
    }

    .access-header p,
    .access-muted {
        color: var(--onyx-muted, #84849a);
        font-size: 12px;
        font-weight: 600;
        line-height: 1.55;
        margin: 0;
    }

    .access-kpis {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .access-kpi {
        min-width: 0;
        padding: 14px;
    }

    .access-kpis-compact {
        align-items: start;
        gap: 8px;
        grid-auto-rows: max-content;
    }

    .access-kpis-compact .access-kpi {
        align-self: start;
        height: auto;
        min-height: 54px;
        padding: 8px 10px;
    }

    .access-kpi span,
    .access-section-label,
    .access-field label,
    .permission-group strong {
        color: var(--onyx-muted, #84849a);
        display: block;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .6px;
        text-transform: uppercase;
    }

    .access-kpi strong {
        color: #ffffff;
        display: block;
        font-size: 18px;
        font-weight: 900;
        line-height: 1.1;
        margin-top: 8px;
        overflow-wrap: anywhere;
    }

    .access-kpis-compact .access-kpi strong {
        font-size: 14px;
        margin-top: 4px;
    }

    .access-kpis-compact .access-kpi span {
        font-size: 9px;
    }

    .users-management-page {
        align-content: start;
        grid-auto-rows: max-content;
    }

    .users-management-page .access-header-compact {
        align-items: center;
        align-self: start;
        height: auto !important;
        min-height: 0 !important;
        padding: 10px 14px !important;
    }

    .users-management-page .access-title {
        align-items: center;
        gap: 10px;
    }

    .users-management-page .access-title-icon {
        flex: 0 0 30px !important;
        font-size: 11px;
        height: 30px !important;
        width: 30px !important;
    }

    .users-management-page .access-header-compact h1 {
        font-size: 16px;
        line-height: 1.1;
        margin: 0 0 2px !important;
    }

    .users-management-page .access-header-compact p {
        font-size: 10px;
        line-height: 1.25;
        margin: 0 !important;
    }

    .users-management-page .access-header-compact .access-button {
        min-height: 30px;
        padding: 0 10px;
    }

    .users-management-page .access-kpis-compact {
        align-items: start;
        grid-auto-rows: max-content;
    }

    .users-management-page .access-kpis-compact .access-kpi {
        align-self: start;
        height: auto !important;
        min-height: 42px !important;
        padding: 7px 9px !important;
    }

    .users-management-page .access-kpis-compact .access-kpi strong {
        font-size: 13px;
        line-height: 1;
        margin-top: 3px;
    }

    .users-management-page .access-kpis-compact .access-kpi span {
        font-size: 8px;
        line-height: 1.1;
    }

    .access-grid {
        display: grid;
        gap: 14px;
        grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
    }

    .access-panel {
        padding: 16px;
    }

    .access-panel-head {
        align-items: center;
        border-bottom: 1px solid rgba(255,255,255,.08);
        display: flex;
        gap: 10px;
        justify-content: space-between;
        margin: -2px 0 14px;
        padding-bottom: 12px;
    }

    .access-panel h2,
    .access-panel h3 {
        color: #ffffff;
        font-size: 13px;
        font-weight: 900;
        margin: 0;
        text-transform: uppercase;
    }

    .access-form {
        display: grid;
        gap: 12px;
    }

    .access-field {
        display: grid;
        gap: 6px;
        min-width: 0;
    }

    .access-field input,
    .access-field select,
    .access-field textarea {
        background: #101016;
        border: 1px solid rgba(255,255,255,.12);
        color: #ffffff;
        font: inherit;
        font-size: 12px;
        font-weight: 700;
        min-height: 38px;
        padding: 8px 10px;
        width: 100%;
    }

    .access-field.compact input,
    .access-field.compact select {
        font-size: 11px;
        min-height: 32px;
        padding: 5px 8px;
    }

    .access-field textarea {
        min-height: 82px;
        resize: vertical;
    }

    .access-field select option {
        background: #050506;
        color: #ffffff;
    }

    .access-field input:focus,
    .access-field select:focus,
    .access-field textarea:focus {
        border-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(255,255,255,.08);
        outline: 0;
    }

    .access-check {
        align-items: center;
        color: #d8d8de;
        display: inline-flex;
        font-size: 12px;
        font-weight: 800;
        gap: 8px;
        line-height: 1.35;
    }

    .access-check input {
        accent-color: #ffffff;
        flex: 0 0 auto;
        height: 15px;
        width: 15px;
    }

    .access-button {
        align-items: center;
        background: #ffffff;
        border: 1px solid #ffffff;
        color: #050506;
        cursor: pointer;
        display: inline-flex;
        font: inherit;
        font-size: 11px;
        font-weight: 900;
        gap: 8px;
        justify-content: center;
        min-height: 38px;
        padding: 0 13px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .access-button:hover {
        background: transparent;
        color: #ffffff;
        text-decoration: none;
    }

    .access-button.secondary {
        background: rgba(255,255,255,.035);
        border-color: rgba(255,255,255,.12);
        color: #ffffff;
    }

    .access-button.secondary:hover {
        background: #ffffff;
        color: #050506;
        text-decoration: none;
    }

    .access-alert {
        background: var(--onyx-surface, #1c1c27);
        border: 1px solid rgba(255,255,255,.12);
        color: #ffffff;
        font-size: 12px;
        font-weight: 800;
        padding: 11px 12px;
    }

    .access-alert.success {
        border-color: rgba(143,240,195,.28);
        color: #8ff0c3;
    }

    .access-alert.error {
        border-color: rgba(255,138,138,.28);
        color: #ff8a8a;
    }

    .access-table-wrap {
        overflow-x: auto;
    }

    .access-table {
        border-collapse: collapse;
        min-width: 860px;
        width: 100%;
    }

    .access-table-square {
        border: 1px solid rgba(255,255,255,.08);
    }

    .access-table th,
    .access-table td {
        border-bottom: 1px solid rgba(255,255,255,.06);
        padding: 12px;
        text-align: left;
        vertical-align: top;
    }

    .access-table th {
        color: var(--onyx-muted, #84849a);
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .access-table td {
        color: #ffffff;
        font-size: 12px;
        font-weight: 650;
    }

    .access-table tbody tr:hover {
        background: rgba(255,255,255,.025);
    }

    .access-table-title {
        color: #ffffff;
        display: block;
        font-size: 12px;
        font-weight: 900;
        margin-bottom: 4px;
    }

    .role-register-table {
        min-width: 760px;
    }

    .user-register-table {
        min-width: 820px;
    }

    .role-register-table th,
    .role-register-table td,
    .user-register-table th,
    .user-register-table td {
        padding: 8px 9px;
    }

    .role-register-table td,
    .user-register-table td {
        font-size: 11px;
        vertical-align: middle;
    }

    .role-register-table .access-table-title,
    .user-register-table .access-table-title {
        font-size: 11px;
        margin-bottom: 2px;
    }

    .access-badge {
        border: 1px solid rgba(255,255,255,.12);
        color: #d8d8de;
        display: inline-flex;
        font-size: 10px;
        font-weight: 900;
        padding: 5px 8px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .access-badge.flat {
        background: transparent;
        border-color: transparent;
        padding: 0;
    }

    .access-badge.success {
        border-color: rgba(143,240,195,.32);
        color: #8ff0c3;
    }

    .access-badge.warning {
        border-color: rgba(255,210,122,.32);
        color: #ffd27a;
    }

    .access-badge.danger {
        border-color: rgba(255,138,138,.32);
        color: #ff8a8a;
    }

    .permission-grid,
    .security-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }

    .permission-group {
        display: grid;
        gap: 8px;
        padding: 12px;
    }

    .permission-group strong {
        color: #ffffff;
    }

    .role-card {
        display: grid;
        gap: 12px;
    }

    .role-card + .role-card {
        border-top: 1px solid rgba(255,255,255,.08);
        margin-top: 16px;
        padding-top: 16px;
    }

    .access-toolbar {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: space-between;
    }

    .access-actions,
    .access-footer-actions {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .role-actions {
        flex-wrap: nowrap;
        gap: 6px;
        white-space: nowrap;
    }

    .access-footer-actions {
        justify-content: flex-end;
    }

    .access-icon-button {
        align-items: center;
        background: rgba(255,255,255,.035);
        border: 1px solid rgba(255,255,255,.12);
        color: #ffffff;
        display: inline-flex;
        flex: 0 0 34px;
        height: 34px;
        justify-content: center;
        text-decoration: none;
        width: 34px;
    }

    .role-actions .access-icon-button {
        flex-basis: 30px;
        height: 30px;
        width: 30px;
    }

    .access-icon-button:hover {
        background: #ffffff;
        border-color: #ffffff;
        color: #050506;
        text-decoration: none;
    }

    .access-chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .access-chip {
        background: rgba(255,255,255,.04);
        border: 1px solid rgba(255,255,255,.1);
        color: #d8d8de;
        display: inline-flex;
        font-size: 10px;
        font-weight: 800;
        padding: 5px 7px;
        text-transform: uppercase;
    }

    .access-check-box {
        background: #101016;
        border: 1px solid rgba(255,255,255,.12);
        min-height: 38px;
        padding: 8px 10px;
    }

    @media (max-width: 1180px) {
        .access-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 980px) {
        .access-header {
            flex-direction: column;
        }

        .access-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .access-kpis {
            grid-template-columns: 1fr;
        }

        .access-title {
            align-items: flex-start;
        }

        .access-button {
            width: 100%;
        }
    }
</style>
