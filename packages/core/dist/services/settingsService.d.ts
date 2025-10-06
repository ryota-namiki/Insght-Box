import { SettingsRolesInputs, SettingsRolesOutputs } from '../domain/screens';
export interface SettingsRepository {
    saveRoles: (inputs: SettingsRolesInputs) => Promise<void>;
    getRoles: () => Promise<SettingsRolesInputs>;
}
export declare class SettingsService {
    private readonly repository;
    constructor(repository: SettingsRepository);
    updateRoles(inputs: SettingsRolesInputs): Promise<SettingsRolesOutputs>;
    getRoles(): Promise<SettingsRolesInputs>;
}
export declare function clampEventUploadMax(value: number): number;
//# sourceMappingURL=settingsService.d.ts.map