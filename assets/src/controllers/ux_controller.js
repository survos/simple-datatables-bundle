import DataTableController from '@pentiminax/ux-datatables/controller.js';
import { captureTable } from '../dom_table.js';

/** Preserve server-rendered Twig cells before upstream clears the table markup. */
export default class extends DataTableController {
    async connect() {
        const options = this.viewValue;
        // Persist the complete dataset in the Stimulus value for reconnects and Turbo snapshots.
        // Never recapture a paginated table, which only contains the current page's rows.
        if (!Object.hasOwn(options, 'data') && !options.ajax) {
            this.viewValue = { ...options, ...captureTable(this.element) };
        }
        await super.connect();
    }
}
