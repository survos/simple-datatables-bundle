/** The component emits one header row and one cell per column, already escaped by Twig. */
export function captureTable(table) {
    const headers = Array.from(table.tHead?.rows[0]?.cells ?? []);
    const data = Array.from(table.tBodies).flatMap(body =>
        Array.from(body.rows, row => {
            if (row.cells.length !== headers.length) {
                throw new Error('UX table rows must have one cell per column.');
            }
            return Array.from(row.cells, cell => cell.innerHTML);
        })
    );
    return {
        columns: headers.map((header, index) => ({
            data: index,
            name: header.dataset.columnName ?? String(index),
            title: header.innerHTML,
        })),
        data,
    };
}
