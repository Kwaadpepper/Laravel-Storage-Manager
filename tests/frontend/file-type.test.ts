import { describe, expect, it } from 'bun:test';
import { getFileTypeInfo } from '../../src/ts/utils/file-type';

describe('getFileTypeInfo', () => {
  it('detects audio files correctly', () => {
    const mp3 = getFileTypeInfo('song.mp3');
    expect(mp3.category).toBe('audio');
    expect(mp3.label).toBe('Audio');
    expect(mp3.colorClass).toContain('purple');

    const flac = getFileTypeInfo('track.FLAC');
    expect(flac.category).toBe('audio');

    const wav = getFileTypeInfo('audio', 'wav');
    expect(wav.category).toBe('audio');
  });

  it('detects video files correctly', () => {
    const mp4 = getFileTypeInfo('movie.mp4');
    expect(mp4.category).toBe('video');
    expect(mp4.label).toBe('Vidéo');
    expect(mp4.colorClass).toContain('rose');

    const mkv = getFileTypeInfo('clip.mkv');
    expect(mkv.category).toBe('video');

    const webm = getFileTypeInfo('video', 'webm');
    expect(webm.category).toBe('video');
  });

  it('detects image files correctly', () => {
    const png = getFileTypeInfo('photo.png');
    expect(png.category).toBe('image');
    expect(png.label).toBe('Image');
    expect(png.colorClass).toContain('cyan');

    const jpg = getFileTypeInfo('banner.JPEG');
    expect(jpg.category).toBe('image');

    const svg = getFileTypeInfo('logo', 'svg');
    expect(svg.category).toBe('image');
  });

  it('detects PDF files correctly', () => {
    const pdf = getFileTypeInfo('document.pdf');
    expect(pdf.category).toBe('pdf');
    expect(pdf.label).toBe('PDF');
    expect(pdf.colorClass).toContain('red');

    const pdfUpper = getFileTypeInfo('INVOICE.PDF');
    expect(pdfUpper.category).toBe('pdf');
  });

  it('detects Excel / spreadsheet files correctly', () => {
    const xlsx = getFileTypeInfo('data.xlsx');
    expect(xlsx.category).toBe('excel');
    expect(xlsx.label).toBe('Excel');
    expect(xlsx.colorClass).toContain('emerald');

    const csv = getFileTypeInfo('export.csv');
    expect(csv.category).toBe('excel');

    const ods = getFileTypeInfo('sheet', 'ods');
    expect(ods.category).toBe('excel');
  });

  it('detects Word / text processor files correctly', () => {
    const docx = getFileTypeInfo('report.docx');
    expect(docx.category).toBe('word');
    expect(docx.label).toBe('Word');
    expect(docx.colorClass).toContain('blue');

    const doc = getFileTypeInfo('letter.doc');
    expect(doc.category).toBe('word');

    const odt = getFileTypeInfo('doc', 'odt');
    expect(odt.category).toBe('word');
  });

  it('detects presentation files correctly', () => {
    const pptx = getFileTypeInfo('slides.pptx');
    expect(pptx.category).toBe('presentation');
    expect(pptx.label).toBe('Présentation');
    expect(pptx.colorClass).toContain('orange');

    const odp = getFileTypeInfo('pitch.odp');
    expect(odp.category).toBe('presentation');
  });

  it('detects archive files correctly', () => {
    const zip = getFileTypeInfo('backup.zip');
    expect(zip.category).toBe('archive');
    expect(zip.label).toBe('Archive');
    expect(zip.colorClass).toContain('amber');

    const tar = getFileTypeInfo('archive.tar.gz');
    expect(tar.category).toBe('archive');

    const rar = getFileTypeInfo('files.rar');
    expect(rar.category).toBe('archive');

    const sevenZ = getFileTypeInfo('data', '7z');
    expect(sevenZ.category).toBe('archive');
  });

  it('detects code files correctly', () => {
    const ts = getFileTypeInfo('main.ts');
    expect(ts.category).toBe('code');
    expect(ts.label).toBe('Code');

    const php = getFileTypeInfo('Controller.php');
    expect(php.category).toBe('code');

    const vue = getFileTypeInfo('App.vue');
    expect(vue.category).toBe('code');

    const json = getFileTypeInfo('package.json');
    expect(json.category).toBe('code');
  });

  it('detects terminal scripts correctly', () => {
    const sh = getFileTypeInfo('deploy.sh');
    expect(sh.category).toBe('terminal');
    expect(sh.label).toBe('Script');

    const bat = getFileTypeInfo('start.bat');
    expect(bat.category).toBe('terminal');
  });

  it('detects security and config files correctly', () => {
    const key = getFileTypeInfo('private.key');
    expect(key.category).toBe('security');

    const cert = getFileTypeInfo('server.crt');
    expect(cert.category).toBe('security');

    const ini = getFileTypeInfo('config.ini');
    expect(ini.category).toBe('config');
  });

  it('detects special filenames without standard extensions', () => {
    const dockerfile = getFileTypeInfo('Dockerfile');
    expect(dockerfile.category).toBe('code');

    const makefile = getFileTypeInfo('Makefile');
    expect(makefile.category).toBe('terminal');

    const gitignore = getFileTypeInfo('.gitignore');
    expect(gitignore.category).toBe('config');

    const env = getFileTypeInfo('.env');
    expect(env.category).toBe('config');

    const envExample = getFileTypeInfo('.env.example');
    expect(envExample.category).toBe('config');
  });

  it('falls back to generic for unknown or missing extensions', () => {
    const unknown = getFileTypeInfo('mystery.xyz123');
    expect(unknown.category).toBe('generic');
    expect(unknown.label).toBe('Fichier');

    const noExt = getFileTypeInfo('some_file_without_extension');
    expect(noExt.category).toBe('generic');

    const empty = getFileTypeInfo('');
    expect(empty.category).toBe('generic');
  });
});
