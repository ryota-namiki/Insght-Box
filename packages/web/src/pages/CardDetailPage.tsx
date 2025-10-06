import { useEffect, useState } from 'react';
import { Link, useParams, useLocation, useNavigate } from 'react-router-dom';
import type { CardDetailOutputs, CardSummary, FileDescriptor, OcrResult, UserMeta } from '@insight-box/core';
import { getCardDetail, getDocumentV1, getDocumentText, getDocumentEntities, getDocumentMetadata, updateCard, deleteCard, type Document } from '../api/client';
import Toast from '../components/Toast';

export default function CardDetailPage(): JSX.Element {
  const { cardId } = useParams<{ cardId: string }>();
  const location = useLocation();
  const navigate = useNavigate();
  const [detail, setDetail] = useState<CardDetailOutputs | null>(null);
  const [document, setDocument] = useState<Document | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [toast, setToast] = useState<{ message: string; type: 'success' | 'error' | 'info' } | null>(null);
  const [isNewAPI, setIsNewAPI] = useState<boolean>(false);
  const [isEditing, setIsEditing] = useState<boolean>(false);
  const [documentId, setDocumentId] = useState<string | null>(null);
  const [imageUrl, setImageUrl] = useState<string | null>(null);
  const [webClipMetadata, setWebClipMetadata] = useState<{ title: string; description: string; url: string | null } | null>(null);
  const [isImageModalOpen, setIsImageModalOpen] = useState<boolean>(false);
  const [cameraImage, setCameraImage] = useState<string | null>(null);
  const [editForm, setEditForm] = useState<{
    title: string;
    companyName: string;
    memo: string;
    tags: string;
  }>({
    title: '',
    companyName: '',
    memo: '',
    tags: ''
  });
  const [isDeleting, setIsDeleting] = useState<boolean>(false);

  useEffect(() => {
    if (!cardId) return;
    void loadCard(cardId);
  }, [cardId]);

  // URLパラメータからトーストメッセージを取得
  useEffect(() => {
    const searchParams = new URLSearchParams(location.search);
    const toastMessage = searchParams.get('toast');
    const toastType = searchParams.get('toastType') as 'success' | 'error' | 'info' || 'success';
    
    if (toastMessage) {
      setToast({ message: toastMessage, type: toastType });
      // URLからパラメータを削除（ブラウザの戻るボタン対応）
      const newUrl = new URL(window.location.href);
      newUrl.searchParams.delete('toast');
      newUrl.searchParams.delete('toastType');
      window.history.replaceState({}, '', newUrl.toString());
    }
  }, [location.search]);

  async function loadCard(id: string): Promise<void> {
    try {
      // カードAPIを使用してカード詳細を取得
      const cardData: any = await getCardDetail(id);
      
      // サーバーからのレスポンスをCardDetailOutputs形式に変換
      const fallbackUser: UserMeta = {
        id: cardData.summary.authorId || 'unknown',
        name: cardData.summary.authorId || 'Unknown User',
        role: 'member',
      };

      const convertedDetail: CardDetailOutputs = {
        header: {
          title: cardData.summary.title,
          companyName: cardData.summary.company,
          event: {
            id: cardData.summary.eventId || 'unknown',
            name: cardData.summary.eventId || 'Unknown Event',
            startDate: cardData.summary.createdAt,
            endDate: cardData.summary.updatedAt,
          },
          author: fallbackUser,
          createdAt: cardData.summary.createdAt,
          updatedAt: cardData.summary.updatedAt,
          priority: 2,
        },
        body: {
          ocr: {
            text: cardData.detail.text || '',
            updatedAt: cardData.summary.updatedAt,
          },
          sourceFiles: [],
          highlights: cardData.detail.memo ? [cardData.detail.memo] : [],
          tags: cardData.summary.tags || [],
        },
        sidebar: {
          relatedCards: [],
          collections: [],
          history: [],
          reactions: cardData.reactions || { views: 0, comments: 0, likes: 0 },
        },
        state: 'success',
      };
      
      setDetail(convertedDetail);
      setIsNewAPI(false);
      
      // カメラ撮影の画像データを設定
      console.log('=== CARD DATA DEBUG ===');
      console.log('Full card data:', cardData);
      console.log('Card data keys:', Object.keys(cardData));
      console.log('Detail keys:', Object.keys(cardData.detail || {}));
      console.log('Camera image:', cardData.detail.cameraImage);
      console.log('Camera image type:', typeof cardData.detail.cameraImage);
      console.log('Memo:', cardData.detail.memo);
      console.log('Memo type:', typeof cardData.detail.memo);
      console.log('Highlights:', convertedDetail.body.highlights);
      console.log('Highlights length:', convertedDetail.body.highlights?.length);
      console.log('Highlights some:', convertedDetail.body.highlights?.some(h => h && h.trim()));
      console.log('=== END DEBUG ===');
      
      if (cardData.detail.cameraImage) {
        console.log('Setting camera image:', cardData.detail.cameraImage.substring(0, 50) + '...');
        setCameraImage(cardData.detail.cameraImage);
      } else {
        console.log('No camera image found');
        setCameraImage(null);
      }
      
      // documentIdが存在する場合、画像URLまたはメタデータを設定
      if (cardData.detail.documentId) {
        setDocumentId(cardData.detail.documentId);
        
        // 先にstateをリセット
        setImageUrl(null);
        setWebClipMetadata(null);
        
        // メタデータを取得（Webクリップの場合）
        try {
          const metadata = await getDocumentMetadata(cardData.detail.documentId);
          if (metadata.metadata && (metadata.metadata.title || metadata.metadata.description)) {
            // Webクリップの場合 - メタデータのみ設定
            setWebClipMetadata({
              title: metadata.metadata.title || '',
              description: metadata.metadata.description || '',
              url: metadata.url
            });
            // imageUrlはnullのまま（画像リクエストを送らない）
          } else {
            // 画像ファイルの場合 - 画像URLのみ設定
            setImageUrl(`/api/v1/documents/${cardData.detail.documentId}/image`);
            // webClipMetadataはnullのまま
          }
        } catch (err) {
          // エラーの場合は画像として扱う
          console.warn('メタデータ取得エラー、画像として扱います:', err);
          setImageUrl(`/api/v1/documents/${cardData.detail.documentId}/image`);
        }
      } else {
        setDocumentId(null);
        setImageUrl(null);
        setWebClipMetadata(null);
      }
    } catch (err) {
      setError((err as Error).message);
    }
  }

  // 編集モードを開始
  function startEditing(): void {
    if (!detail) return;
    
    setEditForm({
      title: detail.header.title,
      companyName: detail.header.companyName || '',
      memo: detail.body.highlights?.[0] || '',
      tags: detail.body.tags?.map(tag => tag.label).join(', ') || ''
    });
    setIsEditing(true);
  }

  // 編集をキャンセル
  function cancelEditing(): void {
    setIsEditing(false);
    setEditForm({
      title: '',
      companyName: '',
      memo: '',
      tags: ''
    });
  }

  // カードを更新
  async function handleUpdate(): Promise<void> {
    if (!cardId || !detail) return;

    try {
      const tags = editForm.tags
        .split(',')
        .map(tag => tag.trim())
        .filter(tag => tag.length > 0)
        .map(tag => ({ id: `tag_${Date.now()}_${Math.random()}`, label: tag }));

      await updateCard(cardId, {
        title: editForm.title,
        companyName: editForm.companyName || undefined,
        eventId: detail.header.event?.id || 'unknown',
        memo: editForm.memo || undefined,
        tags
      });

      setToast({ message: 'カードが正常に更新されました', type: 'success' });
      setIsEditing(false);
      // カード情報を再読み込み
      await loadCard(cardId);
    } catch (err) {
      setToast({ message: `更新に失敗しました: ${(err as Error).message}`, type: 'error' });
    }
  }

  // カードを削除
  async function handleDelete(): Promise<void> {
    if (!cardId) return;

    if (!confirm('このカードを削除してもよろしいですか？この操作は取り消せません。')) {
      return;
    }

    try {
      setIsDeleting(true);
      await deleteCard(cardId);
      setToast({ message: 'カードが正常に削除されました', type: 'success' });
      
      // 削除後、ボードページにリダイレクト
      setTimeout(() => {
        navigate('/marketplace', { state: { toast: 'カードが削除されました', toastType: 'success' } });
      }, 1500);
    } catch (err) {
      setToast({ message: `削除に失敗しました: ${(err as Error).message}`, type: 'error' });
      setIsDeleting(false);
    }
  }

  if (!cardId) {
    return <p>カードIDが指定されていません。</p>;
  }

  if (error) {
    return <p style={{ color: '#dc2626' }}>エラー: {error}</p>;
  }

  return (
    <div className="grid" style={{ gap: 24 }}>
      {/* トースト通知 */}
      {toast && (
        <Toast
          message={toast.message}
          type={toast.type}
          onClose={() => setToast(null)}
        />
      )}
      
      {detail && (
        <section className="section-card">
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 32 }}>
            <Link to="/" className="button" style={{ textDecoration: 'none', fontSize: '14px', display: 'flex', alignItems: 'center', gap: '4px' }}>
              <span className="material-icons" style={{ fontSize: '18px' }}>chevron_left</span>
              戻る
            </Link>
            <div style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
              {!isEditing && (
                <>
                  <button 
                    onClick={startEditing}
                    style={{
                      background: '#007bff',
                      color: 'white',
                      border: 'none',
                      padding: '8px 16px',
                      borderRadius: '6px',
                      cursor: 'pointer',
                      fontSize: '14px',
                      fontWeight: 'bold',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '4px'
                    }}
                  >
                    <span className="material-icons" style={{ fontSize: '18px' }}>edit</span>
                    編集
                  </button>
                  <button 
                    onClick={handleDelete}
                    disabled={isDeleting}
                    style={{
                      background: isDeleting ? '#6c757d' : '#dc3545',
                      color: 'white',
                      border: 'none',
                      padding: '8px 16px',
                      borderRadius: '6px',
                      cursor: isDeleting ? 'not-allowed' : 'pointer',
                      fontSize: '14px',
                      fontWeight: 'bold',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '4px'
                    }}
                  >
                    <span className="material-icons" style={{ fontSize: '18px' }}>delete</span>
                    {isDeleting ? '削除中...' : '削除'}
                  </button>
                </>
              )}
            </div>
          </div>

          <div style={{ marginBottom: 32 }}>
            <h2 className="section-title">{detail.header.title}</h2>
            {isNewAPI && (
              <span className="api-badge" style={{ 
                backgroundColor: '#28a745', 
                color: 'white', 
                padding: '2px 8px', 
                borderRadius: '12px', 
                fontSize: '0.75rem',
                marginLeft: '8px'
              }}>
                🆕 v1 API
              </span>
            )}
            {detail.header.companyName && (
              <p style={{ fontSize: '1.1rem', color: '#374151', margin: '8px 0', display: 'flex', alignItems: 'center', gap: '6px' }}>
                <span className="material-icons" style={{ fontSize: '20px' }}>business</span>
                {detail.header.companyName}
              </p>
            )}
            <div style={{ display: 'flex', gap: 16, alignItems: 'center', marginTop: 8 }}>
              <span className="badge" style={{ backgroundColor: '#e3f2fd', color: '#1976d2', display: 'flex', alignItems: 'center', gap: '4px' }}>
                <span className="material-icons" style={{ fontSize: '16px' }}>event</span>
                {detail.header.event?.name || 'イベント未設定'}
              </span>
              <span style={{ color: '#6b7280', fontSize: '0.9rem' }}>
                {new Date(detail.header.createdAt).toLocaleString()}
              </span>
            </div>
          </div>
 
          {/* 編集フォーム */}
          {isEditing && (
            <div style={{ marginBottom: 24, padding: 20, backgroundColor: '#f8f9fa', borderRadius: 8, border: '2px solid #007bff' }}>
              <h3 style={{ margin: '0 0 16px 0', color: '#007bff' }}>✏️ カードを編集</h3>
              <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
                <div>
                  <label style={{ display: 'block', marginBottom: 4, fontWeight: 'bold', color: '#374151' }}>
                    タイトル
                  </label>
                  <input
                    type="text"
                    value={editForm.title}
                    onChange={(e) => setEditForm(prev => ({ ...prev, title: e.target.value }))}
                    style={{
                      width: '100%',
                      padding: '8px 12px',
                      border: '1px solid #d1d5db',
                      borderRadius: '6px',
                      fontSize: '14px'
                    }}
                    placeholder="カードのタイトルを入力"
                  />
                </div>
                
                <div>
                  <label style={{ display: 'block', marginBottom: 4, fontWeight: 'bold', color: '#374151' }}>
                    会社名
                  </label>
                  <input
                    type="text"
                    value={editForm.companyName}
                    onChange={(e) => setEditForm(prev => ({ ...prev, companyName: e.target.value }))}
                    style={{
                      width: '100%',
                      padding: '8px 12px',
                      border: '1px solid #d1d5db',
                      borderRadius: '6px',
                      fontSize: '14px'
                    }}
                    placeholder="会社名を入力（任意）"
                  />
                </div>
                
                <div>
                  <label style={{ display: 'block', marginBottom: 4, fontWeight: 'bold', color: '#374151' }}>
                    メモ
                  </label>
                  <textarea
                    value={editForm.memo}
                    onChange={(e) => setEditForm(prev => ({ ...prev, memo: e.target.value }))}
                    style={{
                      width: '100%',
                      padding: '8px 12px',
                      border: '1px solid #d1d5db',
                      borderRadius: '6px',
                      fontSize: '14px',
                      minHeight: '80px',
                      resize: 'vertical'
                    }}
                    placeholder="メモを入力（任意）"
                  />
                </div>
                
                <div>
                  <label style={{ display: 'block', marginBottom: 4, fontWeight: 'bold', color: '#374151' }}>
                    タグ
                  </label>
                  <input
                    type="text"
                    value={editForm.tags}
                    onChange={(e) => setEditForm(prev => ({ ...prev, tags: e.target.value }))}
                    style={{
                      width: '100%',
                      padding: '8px 12px',
                      border: '1px solid #d1d5db',
                      borderRadius: '6px',
                      fontSize: '14px'
                    }}
                    placeholder="タグをカンマ区切りで入力（例: 重要, 会議, プロジェクト）"
                  />
                </div>
                
                <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
                  <button
                    onClick={cancelEditing}
                    style={{
                      background: '#6c757d',
                      color: 'white',
                      border: 'none',
                      padding: '8px 16px',
                      borderRadius: '6px',
                      cursor: 'pointer',
                      fontSize: '14px',
                      fontWeight: 'bold'
                    }}
                  >
                    キャンセル
                  </button>
                  <button
                    onClick={handleUpdate}
                    style={{
                      background: '#28a745',
                      color: 'white',
                      border: 'none',
                      padding: '8px 16px',
                      borderRadius: '6px',
                      cursor: 'pointer',
                      fontSize: '14px',
                      fontWeight: 'bold',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '4px'
                    }}
                  >
                    <span className="material-icons" style={{ fontSize: '18px' }}>save</span>
                    保存
                  </button>
                </div>
              </div>
            </div>
          )}

          {/* ソースファイル情報 */}
          {detail.body.sourceFiles && detail.body.sourceFiles.length > 0 && (
            <div style={{ marginBottom: 24, padding: 16, backgroundColor: '#f8f9fa', borderRadius: 8 }}>
              <h3 style={{ margin: '0 0 12px 0', color: '#374151' }}>📎 アップロードファイル</h3>
              {detail.body.sourceFiles.map((file) => (
                <div key={file.id} style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 8 }}>
                  <span style={{ fontSize: '1.2rem' }}>
                    {file.mimeType.startsWith('image/') ? '🖼️' : 
                     file.mimeType === 'application/pdf' ? '📄' : 
                     file.mimeType === 'text/html' ? '🌐' : '📁'}
                  </span>
                  <span style={{ fontWeight: '500' }}>{file.filename}</span>
                  <span style={{ color: '#6b7280', fontSize: '0.9rem' }}>
                    ({Math.round(file.bytes / 1024)}KB)
                  </span>
                  {file.mimeType === 'text/html' && (
                    <span className="badge" style={{ backgroundColor: '#e8f5e8', color: '#2e7d32' }}>
                      Webクリップ
                    </span>
                  )}
                </div>
              ))}
            </div>
          )}

          {/* カメラ撮影の画像プレビュー - カメラ撮影で作成したカードのみ表示 */}
          {cameraImage && (
            <div style={{ marginBottom: 24, padding: 16, backgroundColor: '#f0f9ff', borderRadius: 8, border: '1px solid #bae6fd' }}>
              <h3 style={{ margin: '0 0 12px 0', color: '#0369a1', display: 'flex', alignItems: 'center', gap: '6px' }}>
                <span className="material-icons" style={{ fontSize: '20px' }}>camera_alt</span>
                撮影した画像
              </h3>
              <div style={{ 
                display: 'flex',
                justifyContent: 'center',
                alignItems: 'center',
                padding: 16,
                backgroundColor: '#ffffff',
                borderRadius: 8,
                border: '1px solid #e5e7eb',
              }}>
                <img 
                  src={cameraImage} 
                  alt="撮影した画像" 
                  style={{ 
                    maxWidth: '100%',
                    maxHeight: '400px',
                    objectFit: 'contain',
                    borderRadius: 4,
                    boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
                    cursor: 'pointer'
                  }}
                  onClick={() => setIsImageModalOpen(true)}
                />
              </div>
            </div>
          )}


          {/* Webクリップのメタデータ */}
          {webClipMetadata && (
            <div style={{ marginBottom: 24, padding: 20, backgroundColor: '#f0f9ff', borderRadius: 8, border: '1px solid #bae6fd' }}>
              <h3 style={{ margin: '0 0 12px 0', color: '#0369a1', display: 'flex', alignItems: 'center', gap: '6px' }}>
                <span className="material-icons" style={{ fontSize: '20px' }}>language</span>
                Webクリップ
              </h3>
              {webClipMetadata.title && (
                <h4 style={{ margin: '0 0 12px 0', color: '#0c4a6e', fontSize: '1.2rem', fontWeight: 'bold' }}>
                  {webClipMetadata.title}
                </h4>
              )}
              {webClipMetadata.description && (
                <p style={{ margin: '0 0 12px 0', color: '#0c4a6e', lineHeight: '1.6' }}>
                  {webClipMetadata.description}
                </p>
              )}
              {webClipMetadata.url && (
                <a 
                  href={webClipMetadata.url} 
                  target="_blank" 
                  rel="noopener noreferrer"
                  style={{ 
                    color: '#0284c7', 
                    fontSize: '0.9rem', 
                    textDecoration: 'none',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '4px'
                  }}
                >
                  <span className="material-icons" style={{ fontSize: '16px' }}>link</span>
                  {webClipMetadata.url}
                </a>
              )}
            </div>
          )}

          {/* アップロードされた画像と抽出テキスト（横並び） */}
          {imageUrl && !webClipMetadata && (
            <div style={{ marginBottom: 24, display: 'flex', gap: 16, alignItems: 'flex-start' }}>
              {/* 画像プレビュー */}
              <div style={{ flex: '1', padding: 16, backgroundColor: '#f9fafb', borderRadius: 8, border: '1px solid #e5e7eb' }}>
                <h3 style={{ margin: '0 0 12px 0', color: '#374151', display: 'flex', alignItems: 'center', gap: '6px' }}>
                  <span className="material-icons" style={{ fontSize: '20px' }}>image</span>
                  アップロードされた画像
                </h3>
                <div style={{ 
                  display: 'flex',
                  justifyContent: 'center',
                  alignItems: 'center',
                  padding: 16,
                  backgroundColor: '#ffffff',
                  borderRadius: 8,
                  height: '600px',
                }}>
                  <img 
                    src={imageUrl} 
                    alt="アップロードされたファイル" 
                    style={{ 
                      maxWidth: '100%',
                      maxHeight: '100%',
                      objectFit: 'contain',
                      borderRadius: 4,
                      boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
                      cursor: 'pointer'
                    }}
                    onClick={() => setIsImageModalOpen(true)}
                    onError={(e) => {
                      console.error('画像の読み込みに失敗しました');
                      // エラー時は非表示にする
                      (e.target as HTMLImageElement).style.display = 'none';
                    }}
                  />
                </div>
              </div>

              {/* 抽出されたテキスト */}
              {detail.body.ocr.text && (
                <div style={{ flex: '1', padding: 16, backgroundColor: '#f9fafb', borderRadius: 8, border: '1px solid #e5e7eb' }}>
                  <h3 style={{ margin: '0 0 12px 0', color: '#374151', display: 'flex', alignItems: 'center', gap: '6px' }}>
                    <span className="material-icons" style={{ fontSize: '20px' }}>description</span>
                    抽出されたテキスト
                  </h3>
                  <div style={{ 
                    padding: 16, 
                    backgroundColor: '#fff', 
                    borderRadius: 8, 
                    fontFamily: 'monospace',
                    fontSize: '0.9rem',
                    lineHeight: '1.5',
                    height: '600px',
                    overflowY: 'auto',
                  }}>
                    <p style={{ whiteSpace: 'pre-wrap', margin: 0 }}>{detail.body.ocr.text}</p>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Webクリップメタデータ */}
          {detail.body.webClipMetadata && (
            <div style={{ marginBottom: 24, padding: 16, backgroundColor: '#e3f2fd', borderRadius: 8, border: '1px solid #bbdefb' }}>
              <h3 style={{ margin: '0 0 12px 0', color: '#1565c0' }}>📱 Webクリップ情報</h3>
              <div style={{ marginBottom: '12px' }}>
                <strong>アプリ名:</strong> {detail.body.webClipMetadata.manifest.name}
              </div>
              <div style={{ marginBottom: '12px' }}>
                <strong>説明:</strong> {detail.body.webClipMetadata.manifest.description}
              </div>
              <div style={{ marginBottom: '12px' }}>
                <strong>URL:</strong> 
                <a 
                  href={detail.body.webClipMetadata.manifest.start_url} 
                  target="_blank" 
                  rel="noopener noreferrer"
                  style={{ marginLeft: '8px', color: '#1565c0', textDecoration: 'underline' }}
                >
                  {detail.body.webClipMetadata.manifest.start_url}
                </a>
              </div>
              <div style={{ display: 'flex', gap: '10px', flexWrap: 'wrap' }}>
                <a 
                  href={`/api/webclip/${cardId}/html`}
                  target="_blank"
                  rel="noopener noreferrer"
                  style={{ 
                    background: '#1565c0', 
                    color: 'white', 
                    padding: '8px 16px', 
                    borderRadius: '6px', 
                    textDecoration: 'none',
                    fontSize: '14px',
                    fontWeight: 'bold'
                  }}
                >
                  📱 Webクリップページ
                </a>
                <a 
                  href={`/api/webclip/${cardId}/manifest.json`}
                  target="_blank"
                  rel="noopener noreferrer"
                  style={{ 
                    background: '#2e7d32', 
                    color: 'white', 
                    padding: '8px 16px', 
                    borderRadius: '6px', 
                    textDecoration: 'none',
                    fontSize: '14px',
                    fontWeight: 'bold'
                  }}
                >
                  📄 マニフェスト
                </a>
                <a 
                  href={`/api/webclip/${cardId}/profile.mobileconfig`}
                  download
                  style={{ 
                    background: '#f57c00', 
                    color: 'white', 
                    padding: '8px 16px', 
                    borderRadius: '6px', 
                    textDecoration: 'none',
                    fontSize: '14px',
                    fontWeight: 'bold'
                  }}
                >
                  ⚙️ 構成プロファイル
                </a>
              </div>
            </div>
          )}

          {/* メモ - 常に表示 */}
          <div style={{ marginBottom: 24, padding: 16, backgroundColor: '#f8f9fa', borderRadius: 8, border: '1px solid #e5e7eb' }}>
            <h3 style={{ margin: '0 0 12px 0', color: '#374151', display: 'flex', alignItems: 'center', gap: '6px' }}>
              <span className="material-icons" style={{ fontSize: '20px' }}>note</span>
              メモ
            </h3>
            {detail.body.highlights && detail.body.highlights.length > 0 ? (
              detail.body.highlights.map((highlight, index) => (
                <p key={index} style={{ margin: '0 0 8px 0', color: '#374151', lineHeight: '1.6' }}>
                  {highlight || '(メモなし)'}
                </p>
              ))
            ) : (
              <p style={{ margin: '0 0 8px 0', color: '#6b7280', lineHeight: '1.6', fontStyle: 'italic' }}>
                (メモが入力されていません)
              </p>
            )}
          </div>

          {/* タグ */}
          {detail.body.tags && detail.body.tags.length > 0 && (
            <div style={{ marginBottom: 24 }}>
              <h3 style={{ margin: '0 0 12px 0', color: '#374151' }}>🏷️ タグ</h3>
              <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
                {detail.body.tags.map((tag) => (
                  <span key={tag.id} className="tag-pill" style={{ fontSize: '0.9rem' }}>
                    #{tag.label}
                  </span>
                ))}
              </div>
            </div>
          )}
        </section>
      )}

      {/* 画像モーダル */}
      {isImageModalOpen && (imageUrl || cameraImage) && (
        <div 
          style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            backgroundColor: 'rgba(0, 0, 0, 0.9)',
            display: 'flex',
            justifyContent: 'center',
            alignItems: 'center',
            zIndex: 1000,
            padding: 20,
          }}
          onClick={() => setIsImageModalOpen(false)}
        >
          <div style={{ position: 'relative', maxWidth: '90%', maxHeight: '90%', display: 'inline-block', padding: '20px' }}>
            <button
              onClick={() => setIsImageModalOpen(false)}
              style={{
                position: 'absolute',
                top: 0,
                right: 0,
                background: 'rgba(255, 255, 255, 0.95)',
                border: 'none',
                borderRadius: '50%',
                width: 40,
                height: 40,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                cursor: 'pointer',
                fontSize: '20px',
                color: '#374151',
                boxShadow: '0 2px 8px rgba(0,0,0,0.3)',
              }}
            >
              <span className="material-icons">close</span>
            </button>
            <img 
              src={imageUrl || cameraImage} 
              alt={imageUrl ? "アップロードされたファイル（拡大）" : "撮影した画像（拡大）"} 
              style={{ 
                maxWidth: '100%',
                maxHeight: '90vh',
                objectFit: 'contain',
                borderRadius: 8,
                display: 'block',
              }}
              onClick={(e) => e.stopPropagation()}
            />
          </div>
        </div>
      )}

    </div>
  );
}
