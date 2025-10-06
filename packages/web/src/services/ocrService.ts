import { createWorker } from 'tesseract.js';

export interface OCRResult {
  text: string;
  confidence: number;
  processingTime: number;
}

export interface OCRProgress {
  status: string;
  progress: number;
}

class OCRService {
  private worker: Tesseract.Worker | null = null;
  private isInitialized = false;

  async initialize(): Promise<void> {
    if (this.isInitialized) return;

    try {
      this.worker = await createWorker();

      // 日本語と英語の言語を読み込み
      await this.worker.loadLanguage('jpn+eng');
      await this.worker.initialize('jpn+eng');
      
      // OCR設定を最適化
      await this.worker.setParameters({
        preserve_interword_spaces: '1',
        tessedit_pageseg_mode: '6', // 単一ブロックの文章として認識
      });

      this.isInitialized = true;
      console.log('OCR Service initialized successfully');
    } catch (error) {
      console.error('Failed to initialize OCR service:', error);
      throw new Error('OCRサービスの初期化に失敗しました');
    }
  }

  async recognizeImage(
    imageData: ImageData | HTMLCanvasElement | HTMLImageElement,
    onProgress?: (progress: OCRProgress) => void,
    fastMode = false
  ): Promise<OCRResult> {
    if (!this.worker || !this.isInitialized) {
      await this.initialize();
    }

    if (!this.worker) {
      throw new Error('OCRワーカーが初期化されていません');
    }

    const startTime = Date.now();

    try {
      // 高速モード用の設定
      if (fastMode) {
        await this.worker.setParameters({
          tessedit_pageseg_mode: '6', // 単一ブロック
          tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789あいうえおかきくけこさしすせそたちつてとなにぬねのはひふへほまみむめもやゆよらりるれろわをんがぎぐげござじずぜぞだぢづでどばびぶべぼぱぴぷぺぽゃゅょっー・、。！？（）「」『』【】〈〉《》〔〕［］｛｝',
        });
      }

      const result = await this.worker.recognize(imageData);

      const processingTime = Date.now() - startTime;

      return {
        text: result.data.text,
        confidence: result.data.confidence,
        processingTime,
      };
    } catch (error) {
      console.error('OCR recognition failed:', error);
      throw new Error('画像の文字認識に失敗しました');
    }
  }

  async terminate(): Promise<void> {
    if (this.worker) {
      await this.worker.terminate();
      this.worker = null;
      this.isInitialized = false;
    }
  }

  // 画像前処理関数（自動スキャン用に最適化）
  preprocessImage(canvas: HTMLCanvasElement): HTMLCanvasElement {
    const ctx = canvas.getContext('2d');
    if (!ctx) return canvas;

    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imageData.data;

    // グレースケール変換とコントラスト調整
    for (let i = 0; i < data.length; i += 4) {
      // グレースケール変換（RGB加重平均）
      const gray = data[i] * 0.299 + data[i + 1] * 0.587 + data[i + 2] * 0.114;
      
      // コントラスト調整（自動スキャン用に強化）
      const contrast = Math.max(0, Math.min(255, (gray - 128) * 1.5 + 128));
      
      data[i] = contrast;     // R
      data[i + 1] = contrast; // G
      data[i + 2] = contrast; // B
      // data[i + 3] は alpha チャンネルなのでそのまま
    }

    ctx.putImageData(imageData, 0, 0);
    return canvas;
  }

  // 高速OCR用の軽量前処理
  preprocessImageFast(canvas: HTMLCanvasElement): HTMLCanvasElement {
    const ctx = canvas.getContext('2d');
    if (!ctx) return canvas;

    // 画像サイズを適度に縮小（処理速度向上）
    const scale = Math.min(1, 1200 / Math.max(canvas.width, canvas.height));
    if (scale < 1) {
      const newCanvas = document.createElement('canvas');
      const newCtx = newCanvas.getContext('2d');
      if (!newCtx) return canvas;

      newCanvas.width = Math.round(canvas.width * scale);
      newCanvas.height = Math.round(canvas.height * scale);
      newCtx.drawImage(canvas, 0, 0, newCanvas.width, newCanvas.height);
      return this.preprocessImage(newCanvas);
    }

    return this.preprocessImage(canvas);
  }

  // 画像サイズを最適化
  optimizeImageSize(img: HTMLImageElement, maxWidth = 2000, maxHeight = 2000): HTMLCanvasElement {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    if (!ctx) throw new Error('Canvas context not available');

    // アスペクト比を保持しながらサイズを調整
    const scale = Math.min(maxWidth / img.width, maxHeight / img.height, 1);
    canvas.width = Math.round(img.width * scale);
    canvas.height = Math.round(img.height * scale);

    // 画像を描画
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    
    return canvas;
  }
}

// シングルトンインスタンス
export const ocrService = new OCRService();

