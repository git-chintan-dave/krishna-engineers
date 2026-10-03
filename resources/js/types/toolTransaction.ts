export type ToolTransactionType =
    | 'opening'
    | 'purchase'
    | 'issue'
    | 'return'
    | 'adjustment';

export type ToolTransactionTool = {
    id: number;
    name: string | null;
    type: string | null;
};

export type ToolTransactionUser = {
    id: number | null;
    name: string | null;
    employeeCode: string | null;
};

export type ToolTransactionPart = {
    id: number | null;
    name: string | null;
    referenceName: string | null;
};

export type ToolTransactionSetup = {
    id: number | null;
    name: string | null;
};

export type ToolTransactionCreatedBy = {
    id: number | null;
    name: string | null;
    employeeCode: string | null;
};

export type ToolTransaction = {
    id: number;
    uuid: string;
    tool: ToolTransactionTool;
    type: {
        value: ToolTransactionType | null;
        label: string | null;
    };
    quantity: number;
    fromUser: ToolTransactionUser;
    toUser: ToolTransactionUser;
    part: ToolTransactionPart;
    setup: ToolTransactionSetup;
    returnScrap: boolean | null;
    createdBy: ToolTransactionCreatedBy;
    remarks: string | null;
    createdAt: string | null;
    updatedAt?: string | null;
};
