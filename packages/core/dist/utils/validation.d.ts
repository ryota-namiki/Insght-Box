import { BatchImportInputs, BatchImportOutputs, CardDetailInputs, CardDetailOutputs, MarketplaceInputs, ShareModalInputs, UploadHomeInputs, UploadReviewInputs } from '../domain/screens';
export type ValidationIssue = {
    path: string;
    message: string;
    value?: unknown;
};
export declare function guard(condition: boolean, path: string, message: string, value?: unknown): ValidationIssue[];
export declare function validateUploadHomeInputs(input: UploadHomeInputs): ValidationIssue[];
export declare function validateUploadReviewInputs(input: UploadReviewInputs): ValidationIssue[];
export declare function validateBatchImportInputs(input: BatchImportInputs): ValidationIssue[];
export declare function validateBatchImportOutputs(output: BatchImportOutputs): ValidationIssue[];
export declare function validateCardDetail(inputs: CardDetailInputs, outputs: CardDetailOutputs): ValidationIssue[];
export declare function validateMarketplaceInputs(inputs: MarketplaceInputs): ValidationIssue[];
export declare function validateShareInputs(inputs: ShareModalInputs): ValidationIssue[];
export declare function assertValid(issues: ValidationIssue[]): void;
//# sourceMappingURL=validation.d.ts.map