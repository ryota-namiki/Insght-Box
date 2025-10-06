import { CardSummary, TemplateType } from '../domain/common';
import { BoardHomeInputs, BoardHomeOutputs, TemplateModalInputs, TemplateModalOutputs, TemplateSlot } from '../domain/screens';
type SimilarityFn = (a: CardSummary, b: CardSummary) => number;
export declare class BoardService {
    private readonly cardsProvider;
    private readonly similarity;
    constructor(cardsProvider: () => Promise<CardSummary[]>, similarity: SimilarityFn);
    loadBoard(inputs: BoardHomeInputs): Promise<BoardHomeOutputs>;
    private filterCards;
    private createCanvas;
    private createRelationGraph;
    applyTemplate(inputs: TemplateModalInputs, summarizer: (slot: TemplateSlot) => Promise<string>): Promise<TemplateModalOutputs>;
}
export declare function generateSynthesisSummary(template: TemplateType, slots: TemplateSlot[]): string;
export declare function createTagSimilarity(): SimilarityFn;
export declare function createEventSimilarity(weight: number): SimilarityFn;
export declare function combineSimilarities(...fns: SimilarityFn[]): SimilarityFn;
export {};
//# sourceMappingURL=boardService.d.ts.map