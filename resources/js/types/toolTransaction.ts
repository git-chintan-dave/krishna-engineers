export type ToolTransactionType =
    | 'opening'
    | 'purchase'
    | 'issue'
    | 'adjustment';

export type ToolTransactionTool = {
    id: number;
    name: string | null;
    type: string | null;
};

export type ToolTransactionUser = {
    id: number | null;
    name: string | null;
    employee_code: string | null;
};

export type ToolTransactionPart = {
    id: number | null;
    name: string | null;
    new_part_name: string | null;
};

export type ToolTransactionSetup = {
    id: number | null;
    name: string | null;
};

export type ToolTransactionCreatedBy = {
    id: number | null;
    name: string | null;
    employee_code: string | null;
};

export type ToolTransaction = {
    id: number;
    uuid: string;
    tool: ToolTransactionTool | null;
    type: {
        value: ToolTransactionType | null;
        label: string | null;
    };
    quantity: number;
    from_user: ToolTransactionUser | null;
    to_user: ToolTransactionUser | null;
    part: ToolTransactionPart | null;
    setup: ToolTransactionSetup | null;
    return_scrap: boolean | null;
    created_by: ToolTransactionCreatedBy | null;
    remarks: string | null;
    created_at: string | null;
    updated_at?: string | null;
};
