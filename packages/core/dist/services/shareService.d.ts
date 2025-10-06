import { ShareScope } from '../domain/common';
import { ShareModalInputs, ShareModalOutputs } from '../domain/screens';
export interface SharePublisher {
    createShareLink: (cardId: string, scope: ShareScope, expiresAt: string) => Promise<string>;
    postToSlack: (params: {
        url: string;
        inputs: ShareModalInputs;
    }) => Promise<void>;
}
export declare class ShareService {
    private readonly publisher;
    constructor(publisher: SharePublisher);
    share(inputs: ShareModalInputs): Promise<ShareModalOutputs>;
}
//# sourceMappingURL=shareService.d.ts.map