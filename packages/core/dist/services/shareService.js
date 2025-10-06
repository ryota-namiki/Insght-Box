import { assertValid, validateShareInputs } from '../utils/validation';
export class ShareService {
    constructor(publisher) {
        this.publisher = publisher;
    }
    async share(inputs) {
        assertValid(validateShareInputs(inputs));
        try {
            const url = await this.publisher.createShareLink(inputs.cardId, inputs.scope, inputs.expiresAt);
            let postedToSlack = false;
            if (inputs.slackTargets && inputs.slackTargets.length) {
                try {
                    await this.publisher.postToSlack({ url, inputs });
                    postedToSlack = true;
                }
                catch (error) {
                    return {
                        shareUrl: url,
                        postedToSlack: false,
                        state: 'failure',
                        errors: ['Slack投稿に失敗しました', error.message],
                    };
                }
            }
            return {
                shareUrl: url,
                postedToSlack,
                state: 'success',
            };
        }
        catch (error) {
            if (/offline/i.test(error.message)) {
                return {
                    shareUrl: '',
                    postedToSlack: false,
                    state: 'offline',
                    errors: ['オフラインのためURL生成のみ可能です'],
                };
            }
            return {
                shareUrl: '',
                postedToSlack: false,
                state: 'failure',
                errors: [error.message],
            };
        }
    }
}
//# sourceMappingURL=shareService.js.map