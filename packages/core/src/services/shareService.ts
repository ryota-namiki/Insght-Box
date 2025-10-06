import { ShareScope } from '../domain/common';
import { ShareModalInputs, ShareModalOutputs } from '../domain/screens';
import { assertValid, validateShareInputs } from '../utils/validation';

export interface SharePublisher {
  createShareLink: (cardId: string, scope: ShareScope, expiresAt: string) => Promise<string>;
  postToSlack: (params: { url: string; inputs: ShareModalInputs }) => Promise<void>;
}

export class ShareService {
  constructor(private readonly publisher: SharePublisher) {}

  async share(inputs: ShareModalInputs): Promise<ShareModalOutputs> {
    assertValid(validateShareInputs(inputs));
    try {
      const url = await this.publisher.createShareLink(inputs.cardId, inputs.scope, inputs.expiresAt);
      let postedToSlack = false;
      if (inputs.slackTargets && inputs.slackTargets.length) {
        try {
          await this.publisher.postToSlack({ url, inputs });
          postedToSlack = true;
        } catch (error) {
          return {
            shareUrl: url,
            postedToSlack: false,
            state: 'failure',
            errors: ['Slack投稿に失敗しました', (error as Error).message],
          };
        }
      }
      return {
        shareUrl: url,
        postedToSlack,
        state: 'success',
      };
    } catch (error) {
      if (/offline/i.test((error as Error).message)) {
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
        errors: [(error as Error).message],
      };
    }
  }
}
