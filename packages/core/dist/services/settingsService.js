import { LIMITS } from '../domain/common';
export class SettingsService {
    constructor(repository) {
        this.repository = repository;
    }
    async updateRoles(inputs) {
        if (!['team', 'org'].includes(inputs.defaultScope)) {
            return { applied: false, state: 'failure' };
        }
        if (inputs.eventUploadMax < 1 || inputs.eventUploadMax > 200) {
            return { applied: false, state: 'failure' };
        }
        try {
            await this.repository.saveRoles(inputs);
            return { applied: true, state: 'success' };
        }
        catch (error) {
            if (/offline/i.test(error.message)) {
                return { applied: false, state: 'offline' };
            }
            return { applied: false, state: 'failure' };
        }
    }
    async getRoles() {
        return this.repository.getRoles();
    }
}
export function clampEventUploadMax(value) {
    if (Number.isNaN(value))
        return LIMITS.eventUploadMax;
    return Math.min(Math.max(value, 1), 200);
}
//# sourceMappingURL=settingsService.js.map