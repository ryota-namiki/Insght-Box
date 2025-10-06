import { LIMITS, ShareScope } from '../domain/common';
import { SettingsRolesInputs, SettingsRolesOutputs } from '../domain/screens';

export interface SettingsRepository {
  saveRoles: (inputs: SettingsRolesInputs) => Promise<void>;
  getRoles: () => Promise<SettingsRolesInputs>;
}

export class SettingsService {
  constructor(private readonly repository: SettingsRepository) {}

  async updateRoles(inputs: SettingsRolesInputs): Promise<SettingsRolesOutputs> {
    if (!['team', 'org'].includes(inputs.defaultScope)) {
      return { applied: false, state: 'failure' };
    }
    if (inputs.eventUploadMax < 1 || inputs.eventUploadMax > 200) {
      return { applied: false, state: 'failure' };
    }

    try {
      await this.repository.saveRoles(inputs);
      return { applied: true, state: 'success' };
    } catch (error) {
      if (/offline/i.test((error as Error).message)) {
        return { applied: false, state: 'offline' };
      }
      return { applied: false, state: 'failure' };
    }
  }

  async getRoles(): Promise<SettingsRolesInputs> {
    return this.repository.getRoles();
  }
}

export function clampEventUploadMax(value: number): number {
  if (Number.isNaN(value)) return LIMITS.eventUploadMax;
  return Math.min(Math.max(value, 1), 200);
}
