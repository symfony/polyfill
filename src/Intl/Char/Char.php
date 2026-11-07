<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Polyfill\Intl\Char;

/**
 * Partial implementation of the IntlChar class provided by the intl extension: the properties of code points from
 * the Unicode Character Database, without their names, blocks and property aliases.
 *
 * @internal
 */
class Char
{
    public const UNICODE_VERSION = '17.0';
    public const CODEPOINT_MIN = 0;
    public const CODEPOINT_MAX = 1114111;
    public const NO_NUMERIC_VALUE = -123456789.0;
    public const PROPERTY_ALPHABETIC = 0;
    public const PROPERTY_BINARY_START = 0;
    public const PROPERTY_ASCII_HEX_DIGIT = 1;
    public const PROPERTY_BIDI_CONTROL = 2;
    public const PROPERTY_BIDI_MIRRORED = 3;
    public const PROPERTY_DASH = 4;
    public const PROPERTY_DEFAULT_IGNORABLE_CODE_POINT = 5;
    public const PROPERTY_DEPRECATED = 6;
    public const PROPERTY_DIACRITIC = 7;
    public const PROPERTY_EXTENDER = 8;
    public const PROPERTY_FULL_COMPOSITION_EXCLUSION = 9;
    public const PROPERTY_GRAPHEME_BASE = 10;
    public const PROPERTY_GRAPHEME_EXTEND = 11;
    public const PROPERTY_GRAPHEME_LINK = 12;
    public const PROPERTY_HEX_DIGIT = 13;
    public const PROPERTY_HYPHEN = 14;
    public const PROPERTY_ID_CONTINUE = 15;
    public const PROPERTY_ID_START = 16;
    public const PROPERTY_IDEOGRAPHIC = 17;
    public const PROPERTY_IDS_BINARY_OPERATOR = 18;
    public const PROPERTY_IDS_TRINARY_OPERATOR = 19;
    public const PROPERTY_IDS_UNARY_OPERATOR = 72;
    public const PROPERTY_ID_COMPAT_MATH_START = 73;
    public const PROPERTY_ID_COMPAT_MATH_CONTINUE = 74;
    public const PROPERTY_JOIN_CONTROL = 20;
    public const PROPERTY_LOGICAL_ORDER_EXCEPTION = 21;
    public const PROPERTY_LOWERCASE = 22;
    public const PROPERTY_MATH = 23;
    public const PROPERTY_NONCHARACTER_CODE_POINT = 24;
    public const PROPERTY_QUOTATION_MARK = 25;
    public const PROPERTY_RADICAL = 26;
    public const PROPERTY_SOFT_DOTTED = 27;
    public const PROPERTY_TERMINAL_PUNCTUATION = 28;
    public const PROPERTY_UNIFIED_IDEOGRAPH = 29;
    public const PROPERTY_UPPERCASE = 30;
    public const PROPERTY_WHITE_SPACE = 31;
    public const PROPERTY_XID_CONTINUE = 32;
    public const PROPERTY_XID_START = 33;
    public const PROPERTY_CASE_SENSITIVE = 34;
    public const PROPERTY_S_TERM = 35;
    public const PROPERTY_VARIATION_SELECTOR = 36;
    public const PROPERTY_NFD_INERT = 37;
    public const PROPERTY_NFKD_INERT = 38;
    public const PROPERTY_NFC_INERT = 39;
    public const PROPERTY_NFKC_INERT = 40;
    public const PROPERTY_SEGMENT_STARTER = 41;
    public const PROPERTY_PATTERN_SYNTAX = 42;
    public const PROPERTY_PATTERN_WHITE_SPACE = 43;
    public const PROPERTY_POSIX_ALNUM = 44;
    public const PROPERTY_POSIX_BLANK = 45;
    public const PROPERTY_POSIX_GRAPH = 46;
    public const PROPERTY_POSIX_PRINT = 47;
    public const PROPERTY_POSIX_XDIGIT = 48;
    public const PROPERTY_CASED = 49;
    public const PROPERTY_CASE_IGNORABLE = 50;
    public const PROPERTY_CHANGES_WHEN_LOWERCASED = 51;
    public const PROPERTY_CHANGES_WHEN_UPPERCASED = 52;
    public const PROPERTY_CHANGES_WHEN_TITLECASED = 53;
    public const PROPERTY_CHANGES_WHEN_CASEFOLDED = 54;
    public const PROPERTY_CHANGES_WHEN_CASEMAPPED = 55;
    public const PROPERTY_CHANGES_WHEN_NFKC_CASEFOLDED = 56;
    public const PROPERTY_BINARY_LIMIT = 76;
    public const PROPERTY_BIDI_CLASS = 4096;
    public const PROPERTY_INT_START = 4096;
    public const PROPERTY_BLOCK = 4097;
    public const PROPERTY_CANONICAL_COMBINING_CLASS = 4098;
    public const PROPERTY_DECOMPOSITION_TYPE = 4099;
    public const PROPERTY_EAST_ASIAN_WIDTH = 4100;
    public const PROPERTY_GENERAL_CATEGORY = 4101;
    public const PROPERTY_JOINING_GROUP = 4102;
    public const PROPERTY_JOINING_TYPE = 4103;
    public const PROPERTY_LINE_BREAK = 4104;
    public const PROPERTY_NUMERIC_TYPE = 4105;
    public const PROPERTY_SCRIPT = 4106;
    public const PROPERTY_HANGUL_SYLLABLE_TYPE = 4107;
    public const PROPERTY_NFD_QUICK_CHECK = 4108;
    public const PROPERTY_NFKD_QUICK_CHECK = 4109;
    public const PROPERTY_NFC_QUICK_CHECK = 4110;
    public const PROPERTY_NFKC_QUICK_CHECK = 4111;
    public const PROPERTY_LEAD_CANONICAL_COMBINING_CLASS = 4112;
    public const PROPERTY_TRAIL_CANONICAL_COMBINING_CLASS = 4113;
    public const PROPERTY_GRAPHEME_CLUSTER_BREAK = 4114;
    public const PROPERTY_SENTENCE_BREAK = 4115;
    public const PROPERTY_WORD_BREAK = 4116;
    public const PROPERTY_BIDI_PAIRED_BRACKET_TYPE = 4117;
    public const PROPERTY_INT_LIMIT = 4123;
    public const PROPERTY_GENERAL_CATEGORY_MASK = 8192;
    public const PROPERTY_MASK_START = 8192;
    public const PROPERTY_MASK_LIMIT = 8193;
    public const PROPERTY_NUMERIC_VALUE = 12288;
    public const PROPERTY_DOUBLE_START = 12288;
    public const PROPERTY_DOUBLE_LIMIT = 12289;
    public const PROPERTY_AGE = 16384;
    public const PROPERTY_STRING_START = 16384;
    public const PROPERTY_BIDI_MIRRORING_GLYPH = 16385;
    public const PROPERTY_CASE_FOLDING = 16386;
    public const PROPERTY_ISO_COMMENT = 16387;
    public const PROPERTY_LOWERCASE_MAPPING = 16388;
    public const PROPERTY_NAME = 16389;
    public const PROPERTY_SIMPLE_CASE_FOLDING = 16390;
    public const PROPERTY_SIMPLE_LOWERCASE_MAPPING = 16391;
    public const PROPERTY_SIMPLE_TITLECASE_MAPPING = 16392;
    public const PROPERTY_SIMPLE_UPPERCASE_MAPPING = 16393;
    public const PROPERTY_TITLECASE_MAPPING = 16394;
    public const PROPERTY_UNICODE_1_NAME = 16395;
    public const PROPERTY_UPPERCASE_MAPPING = 16396;
    public const PROPERTY_BIDI_PAIRED_BRACKET = 16397;
    public const PROPERTY_STRING_LIMIT = 16398;
    public const PROPERTY_SCRIPT_EXTENSIONS = 28672;
    public const PROPERTY_OTHER_PROPERTY_START = 28672;
    public const PROPERTY_OTHER_PROPERTY_LIMIT = 28674;
    public const PROPERTY_INVALID_CODE = -1;
    public const CHAR_CATEGORY_UNASSIGNED = 0;
    public const CHAR_CATEGORY_GENERAL_OTHER_TYPES = 0;
    public const CHAR_CATEGORY_UPPERCASE_LETTER = 1;
    public const CHAR_CATEGORY_LOWERCASE_LETTER = 2;
    public const CHAR_CATEGORY_TITLECASE_LETTER = 3;
    public const CHAR_CATEGORY_MODIFIER_LETTER = 4;
    public const CHAR_CATEGORY_OTHER_LETTER = 5;
    public const CHAR_CATEGORY_NON_SPACING_MARK = 6;
    public const CHAR_CATEGORY_ENCLOSING_MARK = 7;
    public const CHAR_CATEGORY_COMBINING_SPACING_MARK = 8;
    public const CHAR_CATEGORY_DECIMAL_DIGIT_NUMBER = 9;
    public const CHAR_CATEGORY_LETTER_NUMBER = 10;
    public const CHAR_CATEGORY_OTHER_NUMBER = 11;
    public const CHAR_CATEGORY_SPACE_SEPARATOR = 12;
    public const CHAR_CATEGORY_LINE_SEPARATOR = 13;
    public const CHAR_CATEGORY_PARAGRAPH_SEPARATOR = 14;
    public const CHAR_CATEGORY_CONTROL_CHAR = 15;
    public const CHAR_CATEGORY_FORMAT_CHAR = 16;
    public const CHAR_CATEGORY_PRIVATE_USE_CHAR = 17;
    public const CHAR_CATEGORY_SURROGATE = 18;
    public const CHAR_CATEGORY_DASH_PUNCTUATION = 19;
    public const CHAR_CATEGORY_START_PUNCTUATION = 20;
    public const CHAR_CATEGORY_END_PUNCTUATION = 21;
    public const CHAR_CATEGORY_CONNECTOR_PUNCTUATION = 22;
    public const CHAR_CATEGORY_OTHER_PUNCTUATION = 23;
    public const CHAR_CATEGORY_MATH_SYMBOL = 24;
    public const CHAR_CATEGORY_CURRENCY_SYMBOL = 25;
    public const CHAR_CATEGORY_MODIFIER_SYMBOL = 26;
    public const CHAR_CATEGORY_OTHER_SYMBOL = 27;
    public const CHAR_CATEGORY_INITIAL_PUNCTUATION = 28;
    public const CHAR_CATEGORY_FINAL_PUNCTUATION = 29;
    public const CHAR_CATEGORY_CHAR_CATEGORY_COUNT = 30;
    public const CHAR_DIRECTION_LEFT_TO_RIGHT = 0;
    public const CHAR_DIRECTION_RIGHT_TO_LEFT = 1;
    public const CHAR_DIRECTION_EUROPEAN_NUMBER = 2;
    public const CHAR_DIRECTION_EUROPEAN_NUMBER_SEPARATOR = 3;
    public const CHAR_DIRECTION_EUROPEAN_NUMBER_TERMINATOR = 4;
    public const CHAR_DIRECTION_ARABIC_NUMBER = 5;
    public const CHAR_DIRECTION_COMMON_NUMBER_SEPARATOR = 6;
    public const CHAR_DIRECTION_BLOCK_SEPARATOR = 7;
    public const CHAR_DIRECTION_SEGMENT_SEPARATOR = 8;
    public const CHAR_DIRECTION_WHITE_SPACE_NEUTRAL = 9;
    public const CHAR_DIRECTION_OTHER_NEUTRAL = 10;
    public const CHAR_DIRECTION_LEFT_TO_RIGHT_EMBEDDING = 11;
    public const CHAR_DIRECTION_LEFT_TO_RIGHT_OVERRIDE = 12;
    public const CHAR_DIRECTION_RIGHT_TO_LEFT_ARABIC = 13;
    public const CHAR_DIRECTION_RIGHT_TO_LEFT_EMBEDDING = 14;
    public const CHAR_DIRECTION_RIGHT_TO_LEFT_OVERRIDE = 15;
    public const CHAR_DIRECTION_POP_DIRECTIONAL_FORMAT = 16;
    public const CHAR_DIRECTION_DIR_NON_SPACING_MARK = 17;
    public const CHAR_DIRECTION_BOUNDARY_NEUTRAL = 18;
    public const CHAR_DIRECTION_FIRST_STRONG_ISOLATE = 19;
    public const CHAR_DIRECTION_LEFT_TO_RIGHT_ISOLATE = 20;
    public const CHAR_DIRECTION_RIGHT_TO_LEFT_ISOLATE = 21;
    public const CHAR_DIRECTION_POP_DIRECTIONAL_ISOLATE = 22;
    public const CHAR_DIRECTION_CHAR_DIRECTION_COUNT = 23;
    public const BLOCK_CODE_NO_BLOCK = 0;
    public const BLOCK_CODE_BASIC_LATIN = 1;
    public const BLOCK_CODE_LATIN_1_SUPPLEMENT = 2;
    public const BLOCK_CODE_LATIN_EXTENDED_A = 3;
    public const BLOCK_CODE_LATIN_EXTENDED_B = 4;
    public const BLOCK_CODE_IPA_EXTENSIONS = 5;
    public const BLOCK_CODE_SPACING_MODIFIER_LETTERS = 6;
    public const BLOCK_CODE_COMBINING_DIACRITICAL_MARKS = 7;
    public const BLOCK_CODE_GREEK = 8;
    public const BLOCK_CODE_CYRILLIC = 9;
    public const BLOCK_CODE_ARMENIAN = 10;
    public const BLOCK_CODE_HEBREW = 11;
    public const BLOCK_CODE_ARABIC = 12;
    public const BLOCK_CODE_SYRIAC = 13;
    public const BLOCK_CODE_THAANA = 14;
    public const BLOCK_CODE_DEVANAGARI = 15;
    public const BLOCK_CODE_BENGALI = 16;
    public const BLOCK_CODE_GURMUKHI = 17;
    public const BLOCK_CODE_GUJARATI = 18;
    public const BLOCK_CODE_ORIYA = 19;
    public const BLOCK_CODE_TAMIL = 20;
    public const BLOCK_CODE_TELUGU = 21;
    public const BLOCK_CODE_KANNADA = 22;
    public const BLOCK_CODE_MALAYALAM = 23;
    public const BLOCK_CODE_SINHALA = 24;
    public const BLOCK_CODE_THAI = 25;
    public const BLOCK_CODE_LAO = 26;
    public const BLOCK_CODE_TIBETAN = 27;
    public const BLOCK_CODE_MYANMAR = 28;
    public const BLOCK_CODE_GEORGIAN = 29;
    public const BLOCK_CODE_HANGUL_JAMO = 30;
    public const BLOCK_CODE_ETHIOPIC = 31;
    public const BLOCK_CODE_CHEROKEE = 32;
    public const BLOCK_CODE_UNIFIED_CANADIAN_ABORIGINAL_SYLLABICS = 33;
    public const BLOCK_CODE_OGHAM = 34;
    public const BLOCK_CODE_RUNIC = 35;
    public const BLOCK_CODE_KHMER = 36;
    public const BLOCK_CODE_MONGOLIAN = 37;
    public const BLOCK_CODE_LATIN_EXTENDED_ADDITIONAL = 38;
    public const BLOCK_CODE_GREEK_EXTENDED = 39;
    public const BLOCK_CODE_GENERAL_PUNCTUATION = 40;
    public const BLOCK_CODE_SUPERSCRIPTS_AND_SUBSCRIPTS = 41;
    public const BLOCK_CODE_CURRENCY_SYMBOLS = 42;
    public const BLOCK_CODE_COMBINING_MARKS_FOR_SYMBOLS = 43;
    public const BLOCK_CODE_LETTERLIKE_SYMBOLS = 44;
    public const BLOCK_CODE_NUMBER_FORMS = 45;
    public const BLOCK_CODE_ARROWS = 46;
    public const BLOCK_CODE_MATHEMATICAL_OPERATORS = 47;
    public const BLOCK_CODE_MISCELLANEOUS_TECHNICAL = 48;
    public const BLOCK_CODE_CONTROL_PICTURES = 49;
    public const BLOCK_CODE_OPTICAL_CHARACTER_RECOGNITION = 50;
    public const BLOCK_CODE_ENCLOSED_ALPHANUMERICS = 51;
    public const BLOCK_CODE_BOX_DRAWING = 52;
    public const BLOCK_CODE_BLOCK_ELEMENTS = 53;
    public const BLOCK_CODE_GEOMETRIC_SHAPES = 54;
    public const BLOCK_CODE_MISCELLANEOUS_SYMBOLS = 55;
    public const BLOCK_CODE_DINGBATS = 56;
    public const BLOCK_CODE_BRAILLE_PATTERNS = 57;
    public const BLOCK_CODE_CJK_RADICALS_SUPPLEMENT = 58;
    public const BLOCK_CODE_KANGXI_RADICALS = 59;
    public const BLOCK_CODE_IDEOGRAPHIC_DESCRIPTION_CHARACTERS = 60;
    public const BLOCK_CODE_CJK_SYMBOLS_AND_PUNCTUATION = 61;
    public const BLOCK_CODE_HIRAGANA = 62;
    public const BLOCK_CODE_KATAKANA = 63;
    public const BLOCK_CODE_BOPOMOFO = 64;
    public const BLOCK_CODE_HANGUL_COMPATIBILITY_JAMO = 65;
    public const BLOCK_CODE_KANBUN = 66;
    public const BLOCK_CODE_BOPOMOFO_EXTENDED = 67;
    public const BLOCK_CODE_ENCLOSED_CJK_LETTERS_AND_MONTHS = 68;
    public const BLOCK_CODE_CJK_COMPATIBILITY = 69;
    public const BLOCK_CODE_CJK_UNIFIED_IDEOGRAPHS_EXTENSION_A = 70;
    public const BLOCK_CODE_CJK_UNIFIED_IDEOGRAPHS = 71;
    public const BLOCK_CODE_YI_SYLLABLES = 72;
    public const BLOCK_CODE_YI_RADICALS = 73;
    public const BLOCK_CODE_HANGUL_SYLLABLES = 74;
    public const BLOCK_CODE_HIGH_SURROGATES = 75;
    public const BLOCK_CODE_HIGH_PRIVATE_USE_SURROGATES = 76;
    public const BLOCK_CODE_LOW_SURROGATES = 77;
    public const BLOCK_CODE_PRIVATE_USE_AREA = 78;
    public const BLOCK_CODE_PRIVATE_USE = 78;
    public const BLOCK_CODE_CJK_COMPATIBILITY_IDEOGRAPHS = 79;
    public const BLOCK_CODE_ALPHABETIC_PRESENTATION_FORMS = 80;
    public const BLOCK_CODE_ARABIC_PRESENTATION_FORMS_A = 81;
    public const BLOCK_CODE_COMBINING_HALF_MARKS = 82;
    public const BLOCK_CODE_CJK_COMPATIBILITY_FORMS = 83;
    public const BLOCK_CODE_SMALL_FORM_VARIANTS = 84;
    public const BLOCK_CODE_ARABIC_PRESENTATION_FORMS_B = 85;
    public const BLOCK_CODE_SPECIALS = 86;
    public const BLOCK_CODE_HALFWIDTH_AND_FULLWIDTH_FORMS = 87;
    public const BLOCK_CODE_OLD_ITALIC = 88;
    public const BLOCK_CODE_GOTHIC = 89;
    public const BLOCK_CODE_DESERET = 90;
    public const BLOCK_CODE_BYZANTINE_MUSICAL_SYMBOLS = 91;
    public const BLOCK_CODE_MUSICAL_SYMBOLS = 92;
    public const BLOCK_CODE_MATHEMATICAL_ALPHANUMERIC_SYMBOLS = 93;
    public const BLOCK_CODE_CJK_UNIFIED_IDEOGRAPHS_EXTENSION_B = 94;
    public const BLOCK_CODE_CJK_COMPATIBILITY_IDEOGRAPHS_SUPPLEMENT = 95;
    public const BLOCK_CODE_TAGS = 96;
    public const BLOCK_CODE_CYRILLIC_SUPPLEMENT = 97;
    public const BLOCK_CODE_CYRILLIC_SUPPLEMENTARY = 97;
    public const BLOCK_CODE_TAGALOG = 98;
    public const BLOCK_CODE_HANUNOO = 99;
    public const BLOCK_CODE_BUHID = 100;
    public const BLOCK_CODE_TAGBANWA = 101;
    public const BLOCK_CODE_MISCELLANEOUS_MATHEMATICAL_SYMBOLS_A = 102;
    public const BLOCK_CODE_SUPPLEMENTAL_ARROWS_A = 103;
    public const BLOCK_CODE_SUPPLEMENTAL_ARROWS_B = 104;
    public const BLOCK_CODE_MISCELLANEOUS_MATHEMATICAL_SYMBOLS_B = 105;
    public const BLOCK_CODE_SUPPLEMENTAL_MATHEMATICAL_OPERATORS = 106;
    public const BLOCK_CODE_KATAKANA_PHONETIC_EXTENSIONS = 107;
    public const BLOCK_CODE_VARIATION_SELECTORS = 108;
    public const BLOCK_CODE_SUPPLEMENTARY_PRIVATE_USE_AREA_A = 109;
    public const BLOCK_CODE_SUPPLEMENTARY_PRIVATE_USE_AREA_B = 110;
    public const BLOCK_CODE_LIMBU = 111;
    public const BLOCK_CODE_TAI_LE = 112;
    public const BLOCK_CODE_KHMER_SYMBOLS = 113;
    public const BLOCK_CODE_PHONETIC_EXTENSIONS = 114;
    public const BLOCK_CODE_MISCELLANEOUS_SYMBOLS_AND_ARROWS = 115;
    public const BLOCK_CODE_YIJING_HEXAGRAM_SYMBOLS = 116;
    public const BLOCK_CODE_LINEAR_B_SYLLABARY = 117;
    public const BLOCK_CODE_LINEAR_B_IDEOGRAMS = 118;
    public const BLOCK_CODE_AEGEAN_NUMBERS = 119;
    public const BLOCK_CODE_UGARITIC = 120;
    public const BLOCK_CODE_SHAVIAN = 121;
    public const BLOCK_CODE_OSMANYA = 122;
    public const BLOCK_CODE_CYPRIOT_SYLLABARY = 123;
    public const BLOCK_CODE_TAI_XUAN_JING_SYMBOLS = 124;
    public const BLOCK_CODE_VARIATION_SELECTORS_SUPPLEMENT = 125;
    public const BLOCK_CODE_ANCIENT_GREEK_MUSICAL_NOTATION = 126;
    public const BLOCK_CODE_ANCIENT_GREEK_NUMBERS = 127;
    public const BLOCK_CODE_ARABIC_SUPPLEMENT = 128;
    public const BLOCK_CODE_BUGINESE = 129;
    public const BLOCK_CODE_CJK_STROKES = 130;
    public const BLOCK_CODE_COMBINING_DIACRITICAL_MARKS_SUPPLEMENT = 131;
    public const BLOCK_CODE_COPTIC = 132;
    public const BLOCK_CODE_ETHIOPIC_EXTENDED = 133;
    public const BLOCK_CODE_ETHIOPIC_SUPPLEMENT = 134;
    public const BLOCK_CODE_GEORGIAN_SUPPLEMENT = 135;
    public const BLOCK_CODE_GLAGOLITIC = 136;
    public const BLOCK_CODE_KHAROSHTHI = 137;
    public const BLOCK_CODE_MODIFIER_TONE_LETTERS = 138;
    public const BLOCK_CODE_NEW_TAI_LUE = 139;
    public const BLOCK_CODE_OLD_PERSIAN = 140;
    public const BLOCK_CODE_PHONETIC_EXTENSIONS_SUPPLEMENT = 141;
    public const BLOCK_CODE_SUPPLEMENTAL_PUNCTUATION = 142;
    public const BLOCK_CODE_SYLOTI_NAGRI = 143;
    public const BLOCK_CODE_TIFINAGH = 144;
    public const BLOCK_CODE_VERTICAL_FORMS = 145;
    public const BLOCK_CODE_NKO = 146;
    public const BLOCK_CODE_BALINESE = 147;
    public const BLOCK_CODE_LATIN_EXTENDED_C = 148;
    public const BLOCK_CODE_LATIN_EXTENDED_D = 149;
    public const BLOCK_CODE_PHAGS_PA = 150;
    public const BLOCK_CODE_PHOENICIAN = 151;
    public const BLOCK_CODE_CUNEIFORM = 152;
    public const BLOCK_CODE_CUNEIFORM_NUMBERS_AND_PUNCTUATION = 153;
    public const BLOCK_CODE_COUNTING_ROD_NUMERALS = 154;
    public const BLOCK_CODE_SUNDANESE = 155;
    public const BLOCK_CODE_LEPCHA = 156;
    public const BLOCK_CODE_OL_CHIKI = 157;
    public const BLOCK_CODE_CYRILLIC_EXTENDED_A = 158;
    public const BLOCK_CODE_VAI = 159;
    public const BLOCK_CODE_CYRILLIC_EXTENDED_B = 160;
    public const BLOCK_CODE_SAURASHTRA = 161;
    public const BLOCK_CODE_KAYAH_LI = 162;
    public const BLOCK_CODE_REJANG = 163;
    public const BLOCK_CODE_CHAM = 164;
    public const BLOCK_CODE_ANCIENT_SYMBOLS = 165;
    public const BLOCK_CODE_PHAISTOS_DISC = 166;
    public const BLOCK_CODE_LYCIAN = 167;
    public const BLOCK_CODE_CARIAN = 168;
    public const BLOCK_CODE_LYDIAN = 169;
    public const BLOCK_CODE_MAHJONG_TILES = 170;
    public const BLOCK_CODE_DOMINO_TILES = 171;
    public const BLOCK_CODE_SAMARITAN = 172;
    public const BLOCK_CODE_UNIFIED_CANADIAN_ABORIGINAL_SYLLABICS_EXTENDED = 173;
    public const BLOCK_CODE_TAI_THAM = 174;
    public const BLOCK_CODE_VEDIC_EXTENSIONS = 175;
    public const BLOCK_CODE_LISU = 176;
    public const BLOCK_CODE_BAMUM = 177;
    public const BLOCK_CODE_COMMON_INDIC_NUMBER_FORMS = 178;
    public const BLOCK_CODE_DEVANAGARI_EXTENDED = 179;
    public const BLOCK_CODE_HANGUL_JAMO_EXTENDED_A = 180;
    public const BLOCK_CODE_JAVANESE = 181;
    public const BLOCK_CODE_MYANMAR_EXTENDED_A = 182;
    public const BLOCK_CODE_TAI_VIET = 183;
    public const BLOCK_CODE_MEETEI_MAYEK = 184;
    public const BLOCK_CODE_HANGUL_JAMO_EXTENDED_B = 185;
    public const BLOCK_CODE_IMPERIAL_ARAMAIC = 186;
    public const BLOCK_CODE_OLD_SOUTH_ARABIAN = 187;
    public const BLOCK_CODE_AVESTAN = 188;
    public const BLOCK_CODE_INSCRIPTIONAL_PARTHIAN = 189;
    public const BLOCK_CODE_INSCRIPTIONAL_PAHLAVI = 190;
    public const BLOCK_CODE_OLD_TURKIC = 191;
    public const BLOCK_CODE_RUMI_NUMERAL_SYMBOLS = 192;
    public const BLOCK_CODE_KAITHI = 193;
    public const BLOCK_CODE_EGYPTIAN_HIEROGLYPHS = 194;
    public const BLOCK_CODE_ENCLOSED_ALPHANUMERIC_SUPPLEMENT = 195;
    public const BLOCK_CODE_ENCLOSED_IDEOGRAPHIC_SUPPLEMENT = 196;
    public const BLOCK_CODE_CJK_UNIFIED_IDEOGRAPHS_EXTENSION_C = 197;
    public const BLOCK_CODE_MANDAIC = 198;
    public const BLOCK_CODE_BATAK = 199;
    public const BLOCK_CODE_ETHIOPIC_EXTENDED_A = 200;
    public const BLOCK_CODE_BRAHMI = 201;
    public const BLOCK_CODE_BAMUM_SUPPLEMENT = 202;
    public const BLOCK_CODE_KANA_SUPPLEMENT = 203;
    public const BLOCK_CODE_PLAYING_CARDS = 204;
    public const BLOCK_CODE_MISCELLANEOUS_SYMBOLS_AND_PICTOGRAPHS = 205;
    public const BLOCK_CODE_EMOTICONS = 206;
    public const BLOCK_CODE_TRANSPORT_AND_MAP_SYMBOLS = 207;
    public const BLOCK_CODE_ALCHEMICAL_SYMBOLS = 208;
    public const BLOCK_CODE_CJK_UNIFIED_IDEOGRAPHS_EXTENSION_D = 209;
    public const BLOCK_CODE_ARABIC_EXTENDED_A = 210;
    public const BLOCK_CODE_ARABIC_MATHEMATICAL_ALPHABETIC_SYMBOLS = 211;
    public const BLOCK_CODE_CHAKMA = 212;
    public const BLOCK_CODE_MEETEI_MAYEK_EXTENSIONS = 213;
    public const BLOCK_CODE_MEROITIC_CURSIVE = 214;
    public const BLOCK_CODE_MEROITIC_HIEROGLYPHS = 215;
    public const BLOCK_CODE_MIAO = 216;
    public const BLOCK_CODE_SHARADA = 217;
    public const BLOCK_CODE_SORA_SOMPENG = 218;
    public const BLOCK_CODE_SUNDANESE_SUPPLEMENT = 219;
    public const BLOCK_CODE_TAKRI = 220;
    public const BLOCK_CODE_BASSA_VAH = 221;
    public const BLOCK_CODE_CAUCASIAN_ALBANIAN = 222;
    public const BLOCK_CODE_COPTIC_EPACT_NUMBERS = 223;
    public const BLOCK_CODE_COMBINING_DIACRITICAL_MARKS_EXTENDED = 224;
    public const BLOCK_CODE_DUPLOYAN = 225;
    public const BLOCK_CODE_ELBASAN = 226;
    public const BLOCK_CODE_GEOMETRIC_SHAPES_EXTENDED = 227;
    public const BLOCK_CODE_GRANTHA = 228;
    public const BLOCK_CODE_KHOJKI = 229;
    public const BLOCK_CODE_KHUDAWADI = 230;
    public const BLOCK_CODE_LATIN_EXTENDED_E = 231;
    public const BLOCK_CODE_LINEAR_A = 232;
    public const BLOCK_CODE_MAHAJANI = 233;
    public const BLOCK_CODE_MANICHAEAN = 234;
    public const BLOCK_CODE_MENDE_KIKAKUI = 235;
    public const BLOCK_CODE_MODI = 236;
    public const BLOCK_CODE_MRO = 237;
    public const BLOCK_CODE_MYANMAR_EXTENDED_B = 238;
    public const BLOCK_CODE_NABATAEAN = 239;
    public const BLOCK_CODE_OLD_NORTH_ARABIAN = 240;
    public const BLOCK_CODE_OLD_PERMIC = 241;
    public const BLOCK_CODE_ORNAMENTAL_DINGBATS = 242;
    public const BLOCK_CODE_PAHAWH_HMONG = 243;
    public const BLOCK_CODE_PALMYRENE = 244;
    public const BLOCK_CODE_PAU_CIN_HAU = 245;
    public const BLOCK_CODE_PSALTER_PAHLAVI = 246;
    public const BLOCK_CODE_SHORTHAND_FORMAT_CONTROLS = 247;
    public const BLOCK_CODE_SIDDHAM = 248;
    public const BLOCK_CODE_SINHALA_ARCHAIC_NUMBERS = 249;
    public const BLOCK_CODE_SUPPLEMENTAL_ARROWS_C = 250;
    public const BLOCK_CODE_TIRHUTA = 251;
    public const BLOCK_CODE_WARANG_CITI = 252;
    public const BLOCK_CODE_COUNT = 347;
    public const BLOCK_CODE_INVALID_CODE = -1;
    public const BPT_NONE = 0;
    public const BPT_OPEN = 1;
    public const BPT_CLOSE = 2;
    public const BPT_COUNT = 3;
    public const EA_NEUTRAL = 0;
    public const EA_AMBIGUOUS = 1;
    public const EA_HALFWIDTH = 2;
    public const EA_FULLWIDTH = 3;
    public const EA_NARROW = 4;
    public const EA_WIDE = 5;
    public const EA_COUNT = 6;
    public const UNICODE_CHAR_NAME = 0;
    public const UNICODE_10_CHAR_NAME = 1;
    public const EXTENDED_CHAR_NAME = 2;
    public const CHAR_NAME_ALIAS = 3;
    public const CHAR_NAME_CHOICE_COUNT = 4;
    public const SHORT_PROPERTY_NAME = 0;
    public const LONG_PROPERTY_NAME = 1;
    public const PROPERTY_NAME_CHOICE_COUNT = 2;
    public const DT_NONE = 0;
    public const DT_CANONICAL = 1;
    public const DT_COMPAT = 2;
    public const DT_CIRCLE = 3;
    public const DT_FINAL = 4;
    public const DT_FONT = 5;
    public const DT_FRACTION = 6;
    public const DT_INITIAL = 7;
    public const DT_ISOLATED = 8;
    public const DT_MEDIAL = 9;
    public const DT_NARROW = 10;
    public const DT_NOBREAK = 11;
    public const DT_SMALL = 12;
    public const DT_SQUARE = 13;
    public const DT_SUB = 14;
    public const DT_SUPER = 15;
    public const DT_VERTICAL = 16;
    public const DT_WIDE = 17;
    public const DT_COUNT = 18;
    public const JT_NON_JOINING = 0;
    public const JT_JOIN_CAUSING = 1;
    public const JT_DUAL_JOINING = 2;
    public const JT_LEFT_JOINING = 3;
    public const JT_RIGHT_JOINING = 4;
    public const JT_TRANSPARENT = 5;
    public const JT_COUNT = 6;
    public const JG_NO_JOINING_GROUP = 0;
    public const JG_AIN = 1;
    public const JG_ALAPH = 2;
    public const JG_ALEF = 3;
    public const JG_BEH = 4;
    public const JG_BETH = 5;
    public const JG_DAL = 6;
    public const JG_DALATH_RISH = 7;
    public const JG_E = 8;
    public const JG_FEH = 9;
    public const JG_FINAL_SEMKATH = 10;
    public const JG_GAF = 11;
    public const JG_GAMAL = 12;
    public const JG_HAH = 13;
    public const JG_TEH_MARBUTA_GOAL = 14;
    public const JG_HAMZA_ON_HEH_GOAL = 14;
    public const JG_HE = 15;
    public const JG_HEH = 16;
    public const JG_HEH_GOAL = 17;
    public const JG_HETH = 18;
    public const JG_KAF = 19;
    public const JG_KAPH = 20;
    public const JG_KNOTTED_HEH = 21;
    public const JG_LAM = 22;
    public const JG_LAMADH = 23;
    public const JG_MEEM = 24;
    public const JG_MIM = 25;
    public const JG_NOON = 26;
    public const JG_NUN = 27;
    public const JG_PE = 28;
    public const JG_QAF = 29;
    public const JG_QAPH = 30;
    public const JG_REH = 31;
    public const JG_REVERSED_PE = 32;
    public const JG_SAD = 33;
    public const JG_SADHE = 34;
    public const JG_SEEN = 35;
    public const JG_SEMKATH = 36;
    public const JG_SHIN = 37;
    public const JG_SWASH_KAF = 38;
    public const JG_SYRIAC_WAW = 39;
    public const JG_TAH = 40;
    public const JG_TAW = 41;
    public const JG_TEH_MARBUTA = 42;
    public const JG_TETH = 43;
    public const JG_WAW = 44;
    public const JG_YEH = 45;
    public const JG_YEH_BARREE = 46;
    public const JG_YEH_WITH_TAIL = 47;
    public const JG_YUDH = 48;
    public const JG_YUDH_HE = 49;
    public const JG_ZAIN = 50;
    public const JG_FE = 51;
    public const JG_KHAPH = 52;
    public const JG_ZHAIN = 53;
    public const JG_BURUSHASKI_YEH_BARREE = 54;
    public const JG_FARSI_YEH = 55;
    public const JG_NYA = 56;
    public const JG_ROHINGYA_YEH = 57;
    public const JG_MANICHAEAN_ALEPH = 58;
    public const JG_MANICHAEAN_AYIN = 59;
    public const JG_MANICHAEAN_BETH = 60;
    public const JG_MANICHAEAN_DALETH = 61;
    public const JG_MANICHAEAN_DHAMEDH = 62;
    public const JG_MANICHAEAN_FIVE = 63;
    public const JG_MANICHAEAN_GIMEL = 64;
    public const JG_MANICHAEAN_HETH = 65;
    public const JG_MANICHAEAN_HUNDRED = 66;
    public const JG_MANICHAEAN_KAPH = 67;
    public const JG_MANICHAEAN_LAMEDH = 68;
    public const JG_MANICHAEAN_MEM = 69;
    public const JG_MANICHAEAN_NUN = 70;
    public const JG_MANICHAEAN_ONE = 71;
    public const JG_MANICHAEAN_PE = 72;
    public const JG_MANICHAEAN_QOPH = 73;
    public const JG_MANICHAEAN_RESH = 74;
    public const JG_MANICHAEAN_SADHE = 75;
    public const JG_MANICHAEAN_SAMEKH = 76;
    public const JG_MANICHAEAN_TAW = 77;
    public const JG_MANICHAEAN_TEN = 78;
    public const JG_MANICHAEAN_TETH = 79;
    public const JG_MANICHAEAN_THAMEDH = 80;
    public const JG_MANICHAEAN_TWENTY = 81;
    public const JG_MANICHAEAN_WAW = 82;
    public const JG_MANICHAEAN_YODH = 83;
    public const JG_MANICHAEAN_ZAYIN = 84;
    public const JG_STRAIGHT_WAW = 85;
    public const JG_COUNT = 106;
    public const GCB_OTHER = 0;
    public const GCB_CONTROL = 1;
    public const GCB_CR = 2;
    public const GCB_EXTEND = 3;
    public const GCB_L = 4;
    public const GCB_LF = 5;
    public const GCB_LV = 6;
    public const GCB_LVT = 7;
    public const GCB_T = 8;
    public const GCB_V = 9;
    public const GCB_SPACING_MARK = 10;
    public const GCB_PREPEND = 11;
    public const GCB_REGIONAL_INDICATOR = 12;
    public const GCB_COUNT = 18;
    public const WB_OTHER = 0;
    public const WB_ALETTER = 1;
    public const WB_FORMAT = 2;
    public const WB_KATAKANA = 3;
    public const WB_MIDLETTER = 4;
    public const WB_MIDNUM = 5;
    public const WB_NUMERIC = 6;
    public const WB_EXTENDNUMLET = 7;
    public const WB_CR = 8;
    public const WB_EXTEND = 9;
    public const WB_LF = 10;
    public const WB_MIDNUMLET = 11;
    public const WB_NEWLINE = 12;
    public const WB_REGIONAL_INDICATOR = 13;
    public const WB_HEBREW_LETTER = 14;
    public const WB_SINGLE_QUOTE = 15;
    public const WB_DOUBLE_QUOTE = 16;
    public const WB_COUNT = 23;
    public const SB_OTHER = 0;
    public const SB_ATERM = 1;
    public const SB_CLOSE = 2;
    public const SB_FORMAT = 3;
    public const SB_LOWER = 4;
    public const SB_NUMERIC = 5;
    public const SB_OLETTER = 6;
    public const SB_SEP = 7;
    public const SB_SP = 8;
    public const SB_STERM = 9;
    public const SB_UPPER = 10;
    public const SB_CR = 11;
    public const SB_EXTEND = 12;
    public const SB_LF = 13;
    public const SB_SCONTINUE = 14;
    public const SB_COUNT = 15;
    public const LB_UNKNOWN = 0;
    public const LB_AMBIGUOUS = 1;
    public const LB_ALPHABETIC = 2;
    public const LB_BREAK_BOTH = 3;
    public const LB_BREAK_AFTER = 4;
    public const LB_BREAK_BEFORE = 5;
    public const LB_MANDATORY_BREAK = 6;
    public const LB_CONTINGENT_BREAK = 7;
    public const LB_CLOSE_PUNCTUATION = 8;
    public const LB_COMBINING_MARK = 9;
    public const LB_CARRIAGE_RETURN = 10;
    public const LB_EXCLAMATION = 11;
    public const LB_GLUE = 12;
    public const LB_HYPHEN = 13;
    public const LB_IDEOGRAPHIC = 14;
    public const LB_INSEPARABLE = 15;
    public const LB_INSEPERABLE = 15;
    public const LB_INFIX_NUMERIC = 16;
    public const LB_LINE_FEED = 17;
    public const LB_NONSTARTER = 18;
    public const LB_NUMERIC = 19;
    public const LB_OPEN_PUNCTUATION = 20;
    public const LB_POSTFIX_NUMERIC = 21;
    public const LB_PREFIX_NUMERIC = 22;
    public const LB_QUOTATION = 23;
    public const LB_COMPLEX_CONTEXT = 24;
    public const LB_SURROGATE = 25;
    public const LB_SPACE = 26;
    public const LB_BREAK_SYMBOLS = 27;
    public const LB_ZWSPACE = 28;
    public const LB_NEXT_LINE = 29;
    public const LB_WORD_JOINER = 30;
    public const LB_H2 = 31;
    public const LB_H3 = 32;
    public const LB_JL = 33;
    public const LB_JT = 34;
    public const LB_JV = 35;
    public const LB_CLOSE_PARENTHESIS = 36;
    public const LB_CONDITIONAL_JAPANESE_STARTER = 37;
    public const LB_HEBREW_LETTER = 38;
    public const LB_REGIONAL_INDICATOR = 39;
    public const LB_COUNT = 49;
    public const NT_NONE = 0;
    public const NT_DECIMAL = 1;
    public const NT_DIGIT = 2;
    public const NT_NUMERIC = 3;
    public const NT_COUNT = 4;
    public const HST_NOT_APPLICABLE = 0;
    public const HST_LEADING_JAMO = 1;
    public const HST_VOWEL_JAMO = 2;
    public const HST_TRAILING_JAMO = 3;
    public const HST_LV_SYLLABLE = 4;
    public const HST_LVT_SYLLABLE = 5;
    public const HST_COUNT = 6;
    public const FOLD_CASE_DEFAULT = 0;
    public const FOLD_CASE_EXCLUDE_SPECIAL_I = 1;

    /** The general categories of each group, as masks of 1 << IntlChar::charType() */
    private const GC_L = 0x3E;
    private const GC_M = 0x1C0;
    private const GC_N = 0xE00;
    private const GC_Z = 0x7000;
    private const GC_C = 0x78001;
    private const GC_P = 0x30F80000;
    private const GC_S = 0xF000000;

    /** @var array<string, array{list<int>, list<int>, list<int>}> The stages of the tables of properties.php */
    private static $tables = [];
    private static $properties;
    private static $bidi;
    private static $category;
    /** @var array<string, int> The code points of the characters given as strings, see codePoint() */
    private static $decoded = [];
    /** @var array<int, array<int, bool|list<int>>> The binary properties by pages of 256 code points, see hasBinaryProperty() */
    private static $pages = [];
    private static $mirroring;
    private static $cases;
    /** @var array<string, array<int, int>> See map() */
    private static $maps = [];
    private static $numeric;
    private static $binary;
    private static $ages;

    public static function charDirection($codepoint)
    {
        if (!\is_int($codepoint) || $codepoint < 0 || $codepoint > 0x10FFFF) {
            if (\is_string($codepoint) && isset(self::$decoded[$codepoint])) {
                $codepoint = self::$decoded[$codepoint];
            } elseif (null === $codepoint = self::codePoint($codepoint)) {
                return null;
            }
        }
        $table = self::$bidi ?: self::$bidi = self::table('bidi');

        return $table[2][$table[1][$table[0][$codepoint >> 10] << 6 | $codepoint >> 4 & 0x3F] << 4 | $codepoint & 0xF];
    }

    public static function charType($codepoint)
    {
        if (!\is_int($codepoint) || $codepoint < 0 || $codepoint > 0x10FFFF) {
            if (\is_string($codepoint) && isset(self::$decoded[$codepoint])) {
                $codepoint = self::$decoded[$codepoint];
            } elseif (null === $codepoint = self::codePoint($codepoint)) {
                return null;
            }
        }
        $table = self::$category ?: self::$category = self::table('category');

        return $table[2][$table[1][$table[0][$codepoint >> 10] << 6 | $codepoint >> 4 & 0x3F] << 4 | $codepoint & 0xF];
    }

    public static function getCombiningClass($codepoint)
    {
        return null === ($codepoint = self::codePoint($codepoint)) ? null : self::value('combining', $codepoint);
    }

    public static function enumCharTypes($callback): void
    {
        $start = 0;
        $type = self::value('category', 0);
        for ($codepoint = 1; $codepoint <= self::CODEPOINT_MAX; ++$codepoint) {
            if ($type !== $next = self::value('category', $codepoint)) {
                $callback($start, $codepoint, $type);
                $start = $codepoint;
                $type = $next;
            }
        }
        $callback($start, self::CODEPOINT_MAX + 1, $type);
    }

    public static function charMirror($codepoint)
    {
        return self::mapped('mirror', $codepoint);
    }

    public static function getBidiPairedBracket($codepoint)
    {
        return self::mapped('bracket', $codepoint);
    }

    public static function tolower($codepoint)
    {
        return self::mapped('lower', $codepoint);
    }

    public static function toupper($codepoint)
    {
        return self::mapped('upper', $codepoint);
    }

    public static function totitle($codepoint)
    {
        return self::mapped('title', $codepoint);
    }

    public static function foldCase($codepoint, $options = self::FOLD_CASE_DEFAULT)
    {
        return self::mapped(self::FOLD_CASE_EXCLUDE_SPECIAL_I === ($options & self::FOLD_CASE_EXCLUDE_SPECIAL_I) ? 'turkic' : 'fold', $codepoint);
    }

    public static function isMirrored($codepoint)
    {
        return self::hasBinaryProperty($codepoint, self::PROPERTY_BIDI_MIRRORED);
    }

    public static function isUAlphabetic($codepoint)
    {
        return self::hasBinaryProperty($codepoint, self::PROPERTY_ALPHABETIC);
    }

    public static function isULowercase($codepoint)
    {
        return self::hasBinaryProperty($codepoint, self::PROPERTY_LOWERCASE);
    }

    public static function isUUppercase($codepoint)
    {
        return self::hasBinaryProperty($codepoint, self::PROPERTY_UPPERCASE);
    }

    public static function isUWhiteSpace($codepoint)
    {
        return self::hasBinaryProperty($codepoint, self::PROPERTY_WHITE_SPACE);
    }

    public static function isalpha($codepoint)
    {
        return self::in($codepoint, self::GC_L);
    }

    public static function islower($codepoint)
    {
        return self::in($codepoint, 1 << self::CHAR_CATEGORY_LOWERCASE_LETTER);
    }

    public static function isupper($codepoint)
    {
        return self::in($codepoint, 1 << self::CHAR_CATEGORY_UPPERCASE_LETTER);
    }

    public static function istitle($codepoint)
    {
        return self::in($codepoint, 1 << self::CHAR_CATEGORY_TITLECASE_LETTER);
    }

    public static function isdigit($codepoint)
    {
        return self::in($codepoint, 1 << self::CHAR_CATEGORY_DECIMAL_DIGIT_NUMBER);
    }

    public static function isalnum($codepoint)
    {
        return self::in($codepoint, self::GC_L | 1 << self::CHAR_CATEGORY_DECIMAL_DIGIT_NUMBER);
    }

    public static function ispunct($codepoint)
    {
        return self::in($codepoint, self::GC_P);
    }

    public static function isdefined($codepoint)
    {
        return self::in($codepoint, ~(1 << self::CHAR_CATEGORY_UNASSIGNED));
    }

    public static function isbase($codepoint)
    {
        return self::in($codepoint, self::GC_L | self::GC_N | 1 << self::CHAR_CATEGORY_COMBINING_SPACING_MARK | 1 << self::CHAR_CATEGORY_ENCLOSING_MARK);
    }

    public static function iscntrl($codepoint)
    {
        return self::in($codepoint, 1 << self::CHAR_CATEGORY_CONTROL_CHAR | 1 << self::CHAR_CATEGORY_FORMAT_CHAR | 1 << self::CHAR_CATEGORY_LINE_SEPARATOR | 1 << self::CHAR_CATEGORY_PARAGRAPH_SEPARATOR);
    }

    public static function isprint($codepoint)
    {
        return self::in($codepoint, ~self::GC_C);
    }

    public static function isgraph($codepoint)
    {
        return self::in($codepoint, ~(1 << self::CHAR_CATEGORY_CONTROL_CHAR | 1 << self::CHAR_CATEGORY_FORMAT_CHAR | 1 << self::CHAR_CATEGORY_SURROGATE | 1 << self::CHAR_CATEGORY_UNASSIGNED | self::GC_Z));
    }

    public static function isJavaSpaceChar($codepoint)
    {
        return self::in($codepoint, self::GC_Z);
    }

    public static function isIDStart($codepoint)
    {
        return self::hasBinaryProperty($codepoint, self::PROPERTY_ID_START);
    }

    public static function isJavaIDStart($codepoint)
    {
        return self::in($codepoint, self::GC_L | 1 << self::CHAR_CATEGORY_CURRENCY_SYMBOL | 1 << self::CHAR_CATEGORY_CONNECTOR_PUNCTUATION);
    }

    public static function isIDPart($codepoint)
    {
        return self::hasBinaryProperty($codepoint, self::PROPERTY_ID_CONTINUE);
    }

    public static function isJavaIDPart($codepoint)
    {
        if (null === $result = self::in($codepoint, self::GC_L | 1 << self::CHAR_CATEGORY_CURRENCY_SYMBOL | 1 << self::CHAR_CATEGORY_CONNECTOR_PUNCTUATION | 1 << self::CHAR_CATEGORY_DECIMAL_DIGIT_NUMBER | 1 << self::CHAR_CATEGORY_LETTER_NUMBER | 1 << self::CHAR_CATEGORY_COMBINING_SPACING_MARK | 1 << self::CHAR_CATEGORY_NON_SPACING_MARK)) {
            return null;
        }

        return $result || self::isIDIgnorable($codepoint);
    }

    public static function isIDIgnorable($codepoint)
    {
        if (null === $codepoint = self::codePoint($codepoint)) {
            return null;
        }

        return $codepoint <= 0x9F ? self::isControl($codepoint) && !self::isAsciiSpace($codepoint) : self::CHAR_CATEGORY_FORMAT_CHAR === self::value('category', $codepoint);
    }

    public static function isISOControl($codepoint)
    {
        return null === ($codepoint = self::codePoint($codepoint)) ? null : self::isControl($codepoint);
    }

    public static function isspace($codepoint)
    {
        if (null === $codepoint = self::codePoint($codepoint)) {
            return null;
        }

        return 0 !== (1 << self::value('category', $codepoint) & self::GC_Z) || ($codepoint <= 0x9F && ((0x09 <= $codepoint && $codepoint <= 0x0D) || (0x1C <= $codepoint && $codepoint <= 0x1F) || 0x85 === $codepoint));
    }

    public static function isWhitespace($codepoint)
    {
        if (null === $codepoint = self::codePoint($codepoint)) {
            return null;
        }

        return (0 !== (1 << self::value('category', $codepoint) & self::GC_Z) && 0xA0 !== $codepoint && 0x2007 !== $codepoint && 0x202F !== $codepoint) || self::isAsciiSpace($codepoint);
    }

    public static function isblank($codepoint)
    {
        if (null === $codepoint = self::codePoint($codepoint)) {
            return null;
        }

        return $codepoint <= 0x9F ? 0x09 === $codepoint || 0x20 === $codepoint : self::CHAR_CATEGORY_SPACE_SEPARATOR === self::value('category', $codepoint);
    }

    public static function isxdigit($codepoint)
    {
        if (null === $codepoint = self::codePoint($codepoint)) {
            return null;
        }

        return (0x41 <= $codepoint && $codepoint <= 0x66 && ($codepoint <= 0x46 || 0x61 <= $codepoint))
            || (0xFF21 <= $codepoint && $codepoint <= 0xFF46 && ($codepoint <= 0xFF26 || 0xFF41 <= $codepoint))
            || self::CHAR_CATEGORY_DECIMAL_DIGIT_NUMBER === self::value('category', $codepoint);
    }

    public static function charDigitValue($codepoint)
    {
        if (null === $codepoint = self::codePoint($codepoint)) {
            return null;
        }

        return self::CHAR_CATEGORY_DECIMAL_DIGIT_NUMBER === self::value('category', $codepoint) ? (int) self::numeric($codepoint) : -1;
    }

    public static function digit($codepoint, $base = 10)
    {
        if (null === $codepoint = self::codePoint($codepoint)) {
            return null;
        }
        if ($base < 2 || $base > 36) {
            return false;
        }
        $value = self::charDigitValue($codepoint);
        if ($value < 0) {
            if (0x61 <= $codepoint && $codepoint <= 0x7A) {
                $value = $codepoint - 0x57;
            } elseif (0x41 <= $codepoint && $codepoint <= 0x5A) {
                $value = $codepoint - 0x37;
            } elseif (0xFF41 <= $codepoint && $codepoint <= 0xFF5A) {
                $value = $codepoint - 0xFF37;
            } elseif (0xFF21 <= $codepoint && $codepoint <= 0xFF3A) {
                $value = $codepoint - 0xFF17;
            }
        }

        return 0 <= $value && $value < $base ? $value : false;
    }

    public static function forDigit($digit, $base = 10)
    {
        if ($base < 2 || $base > 36 || $digit < 0 || $digit >= $base) {
            return 0;
        }

        return $digit < 10 ? 0x30 + $digit : 0x57 + $digit;
    }

    public static function getNumericValue($codepoint)
    {
        if (null === $codepoint = self::codePoint($codepoint)) {
            return null;
        }
        $value = self::numeric($codepoint);

        return null === $value ? (float) self::NO_NUMERIC_VALUE : $value;
    }

    public static function charAge($codepoint)
    {
        if (null === $codepoint = self::codePoint($codepoint)) {
            return null;
        }
        if (null === self::$ages) {
            $data = self::$properties ?: self::$properties = require __DIR__.'/Resources/unidata/properties.php';
            self::$ages = [self::bounds($data['age'][0]), base64_decode($data['age'][1]), $data['age'][2]];
        }
        [$starts, $versions, $names] = self::$ages;
        $run = self::run($starts, $codepoint);
        $version = '' !== $names[\ord($versions[$run])] ? explode('.', $names[\ord($versions[$run])]) : [0, 0];

        return [(int) $version[0], (int) $version[1], 0, 0];
    }

    public static function getIntPropertyValue($codepoint, $property)
    {
        switch ($property) {
            case self::PROPERTY_BIDI_CLASS:
                return self::charDirection($codepoint);
            case self::PROPERTY_GENERAL_CATEGORY:
                return self::charType($codepoint);
            case self::PROPERTY_GENERAL_CATEGORY_MASK:
                return null === ($type = self::charType($codepoint)) ? null : 1 << $type;
            case self::PROPERTY_CANONICAL_COMBINING_CLASS:
                return self::getCombiningClass($codepoint);
            case self::PROPERTY_BIDI_PAIRED_BRACKET_TYPE:
                if ((!\is_int($codepoint) || $codepoint < 0 || $codepoint > 0x10FFFF) && null === $codepoint = self::codePoint($codepoint)) {
                    return null;
                }
                $mirroring = self::$mirroring ?: self::mirroring();

                return isset($mirroring['brackets'][$codepoint]) ? $mirroring['brackets'][$codepoint][1] : self::BPT_NONE;
        }
        if (0 <= $property && $property < self::PROPERTY_BINARY_LIMIT) {
            return null === ($has = self::hasBinaryProperty($codepoint, $property)) ? null : (int) $has;
        }

        throw new \LogicException(\sprintf('The property %d of IntlChar::getIntPropertyValue() is not provided by symfony/polyfill-intl-char.', $property));
    }

    public static function getIntPropertyMinValue($property)
    {
        return 0;
    }

    public static function getIntPropertyMaxValue($property)
    {
        switch ($property) {
            case self::PROPERTY_BIDI_CLASS:
                return self::CHAR_DIRECTION_CHAR_DIRECTION_COUNT - 1;
            case self::PROPERTY_GENERAL_CATEGORY:
                return self::CHAR_CATEGORY_CHAR_CATEGORY_COUNT - 1;
            case self::PROPERTY_CANONICAL_COMBINING_CLASS:
                return 255;
            case self::PROPERTY_BIDI_PAIRED_BRACKET_TYPE:
                return self::BPT_COUNT - 1;
        }
        if (0 <= $property && $property < self::PROPERTY_BINARY_LIMIT) {
            return 1;
        }

        throw new \LogicException(\sprintf('The property %d of IntlChar::getIntPropertyMaxValue() is not provided by symfony/polyfill-intl-char.', $property));
    }

    public static function hasBinaryProperty($codepoint, $property)
    {
        if (!isset(self::$pages[$property])) {
            self::pages($property);
        }
        if (!\is_int($codepoint) || $codepoint < 0 || $codepoint > 0x10FFFF) {
            if (\is_string($codepoint) && isset(self::$decoded[$codepoint])) {
                $codepoint = self::$decoded[$codepoint];
            } elseif (null === $codepoint = self::codePoint($codepoint)) {
                return null;
            }
        }
        $pages = self::$pages[$property];
        if (!isset($pages[$codepoint >> 8])) {
            return false;
        }
        if (true === $page = $pages[$codepoint >> 8]) {
            return true;
        }

        // A bit of a word of 32 code points
        return 0 !== ($page[$codepoint >> 5 & 7] >> ($codepoint & 31) & 1);
    }

    public static function ord($character)
    {
        return self::codePoint($character);
    }

    public static function chr($codepoint)
    {
        if (null === $codepoint = self::codePoint($codepoint)) {
            return null;
        }
        if ($codepoint < 0x80) {
            return \chr($codepoint);
        }
        if ($codepoint < 0x800) {
            return \chr(0xC0 | $codepoint >> 6).\chr(0x80 | $codepoint & 0x3F);
        }
        if ($codepoint < 0x10000) {
            return \chr(0xE0 | $codepoint >> 12).\chr(0x80 | $codepoint >> 6 & 0x3F).\chr(0x80 | $codepoint & 0x3F);
        }

        return \chr(0xF0 | $codepoint >> 18).\chr(0x80 | $codepoint >> 12 & 0x3F).\chr(0x80 | $codepoint >> 6 & 0x3F).\chr(0x80 | $codepoint & 0x3F);
    }

    public static function getUnicodeVersion()
    {
        return [17, 0, 0, 0];
    }

    public static function charName($codepoint, $type = self::UNICODE_CHAR_NAME): void
    {
        throw new \LogicException('IntlChar::charName() is not provided by symfony/polyfill-intl-char.');
    }

    public static function charFromName($name, $type = self::UNICODE_CHAR_NAME): void
    {
        throw new \LogicException('IntlChar::charFromName() is not provided by symfony/polyfill-intl-char.');
    }

    public static function enumCharNames($start, $end, $callback, $type = self::UNICODE_CHAR_NAME): void
    {
        throw new \LogicException('IntlChar::enumCharNames() is not provided by symfony/polyfill-intl-char.');
    }

    public static function getBlockCode($codepoint): void
    {
        throw new \LogicException('IntlChar::getBlockCode() is not provided by symfony/polyfill-intl-char.');
    }

    public static function getFC_NFKC_Closure($codepoint): void
    {
        throw new \LogicException('IntlChar::getFC_NFKC_Closure() is not provided by symfony/polyfill-intl-char.');
    }

    public static function getPropertyEnum($alias): void
    {
        throw new \LogicException('IntlChar::getPropertyEnum() is not provided by symfony/polyfill-intl-char.');
    }

    public static function getPropertyName($property, $type = self::LONG_PROPERTY_NAME): void
    {
        throw new \LogicException('IntlChar::getPropertyName() is not provided by symfony/polyfill-intl-char.');
    }

    public static function getPropertyValueEnum($property, $name): void
    {
        throw new \LogicException('IntlChar::getPropertyValueEnum() is not provided by symfony/polyfill-intl-char.');
    }

    public static function getPropertyValueName($property, $value, $type = self::LONG_PROPERTY_NAME): void
    {
        throw new \LogicException('IntlChar::getPropertyValueName() is not provided by symfony/polyfill-intl-char.');
    }

    /**
     * The value of a code point in a three-stage table: its block of 1024 code points, in it its block of 16, of which
     * the values are bytes.
     */
    private static function value($name, $codepoint)
    {
        $table = self::$tables[$name] ?? self::table($name);

        return $table[2][$table[1][$table[0][$codepoint >> 10] << 6 | $codepoint >> 4 & 0x3F] << 4 | $codepoint & 0xF];
    }

    /**
     * The stages of a three-stage table, unpacked to arrays when first used.
     */
    private static function table($name)
    {
        if (!isset(self::$tables[$name])) {
            $data = self::$properties ?: self::$properties = require __DIR__.'/Resources/unidata/properties.php';
            $bytes = base64_decode($data[$name][0]);
            self::$tables[$name] = [
                array_values(unpack('n*', substr($bytes, 0, 2176))),
                array_values(unpack('n*', substr($bytes, 2176, $data[$name][1] - 2176))),
                array_values(unpack('C*', substr($bytes, $data[$name][1]))),
            ];
        }

        return self::$tables[$name];
    }

    /**
     * Whether the general category of a code point is in a mask.
     */
    private static function in($codepoint, $mask)
    {
        if (!\is_int($codepoint) || $codepoint < 0 || $codepoint > 0x10FFFF) {
            if (\is_string($codepoint) && isset(self::$decoded[$codepoint])) {
                $codepoint = self::$decoded[$codepoint];
            } elseif (null === $codepoint = self::codePoint($codepoint)) {
                return null;
            }
        }
        $table = self::$category ?: self::$category = self::table('category');

        return 0 !== (1 << $table[2][$table[1][$table[0][$codepoint >> 10] << 6 | $codepoint >> 4 & 0x3F] << 4 | $codepoint & 0xF] & $mask);
    }

    /**
     * A code point through a mapping, in the type it is given as: the character given when it is unchanged.
     */
    private static function mapped($name, $codepoint)
    {
        $given = $codepoint;
        if (!\is_int($codepoint) || $codepoint < 0 || $codepoint > 0x10FFFF) {
            if (\is_string($codepoint) && isset(self::$decoded[$codepoint])) {
                $codepoint = self::$decoded[$codepoint];
            } elseif (null === $codepoint = self::codePoint($codepoint)) {
                return null;
            }
        }
        $map = self::$maps[$name] ?? self::map($name);
        if (!isset($map[$codepoint])) {
            return $given;
        }

        return \is_string($given) ? self::chr($map[$codepoint]) : $map[$codepoint];
    }

    /**
     * The code points a mapping changes: the titlecase mappings over the uppercase ones, the foldings over the
     * lowercase ones, the Turkic ones over them.
     */
    private static function map($name)
    {
        if ('mirror' === $name || 'bracket' === $name) {
            $mirroring = self::mirroring();
            if ('mirror' === $name) {
                return self::$maps[$name] = $mirroring['mirrors'];
            }
            $map = [];
            foreach ($mirroring['brackets'] as $from => $bracket) {
                $map[$from] = $bracket[0];
            }

            return self::$maps[$name] = $map;
        }
        if (null === self::$cases) {
            self::$cases = require __DIR__.'/Resources/unidata/caseMapping.php';
        }
        $cases = self::$cases;
        switch ($name) {
            case 'title':
                $map = $cases['title'] + $cases['upper'];
                break;
            case 'fold':
                $map = $cases['fold'] + $cases['lower'];
                break;
            case 'turkic':
                $map = $cases['turkic'] + $cases['fold'] + $cases['lower'];
                break;
            default:
                $map = $cases[$name];
        }
        // A code point mapped to itself is not changed
        foreach ($map as $from => $to) {
            if ($from === $to) {
                unset($map[$from]);
            }
        }

        return self::$maps[$name] = $map;
    }

    private static function numeric($codepoint)
    {
        if (null === self::$numeric) {
            self::$numeric = require __DIR__.'/Resources/unidata/numericValue.php';
        }

        return isset(self::$numeric[$codepoint]) ? (float) self::$numeric[$codepoint] : null;
    }

    private static function mirroring()
    {
        if (null === self::$mirroring) {
            self::$mirroring = require __DIR__.'/Resources/unidata/bidiMirroring.php';
        }

        return self::$mirroring;
    }

    /**
     * Decodes the bounds of runs or ranges: the base64 of the deltas between them, 7 bits a byte.
     */
    private static function bounds($encoded)
    {
        $bytes = base64_decode($encoded);
        $bounds = [];
        $bound = -1;
        $delta = 0;
        $shift = 0;
        for ($i = 0, $length = \strlen($bytes); $i < $length; ++$i) {
            $byte = \ord($bytes[$i]);
            $delta |= ($byte & 0x7F) << $shift;
            if ($byte < 0x80) {
                $bounds[] = $bound += $delta;
                $delta = 0;
                $shift = 0;
            } else {
                $shift += 7;
            }
        }

        return $bounds;
    }

    /**
     * Splits the ranges of a binary property by pages of 256 code points: none of them (no entry), all of them (true),
     * or a bitmap of the page in 8 words of 32 code points.
     */
    private static function pages($property): void
    {
        if (null === self::$binary) {
            self::$binary = require __DIR__.'/Resources/unidata/binaryProperties.php';
        }
        if (!isset(self::$binary[$property])) {
            throw new \LogicException(\sprintf('The property %d of IntlChar::hasBinaryProperty() is not provided by symfony/polyfill-intl-char.', $property));
        }
        $pages = [];
        $bounds = self::bounds(self::$binary[$property]);
        for ($i = 0, $count = \count($bounds); $i < $count; $i += 2) {
            for ($codepoint = $bounds[$i], $end = $bounds[$i + 1]; $codepoint <= $end; ++$codepoint) {
                $page = $codepoint >> 8;
                // A whole page at once
                if (0 === ($codepoint & 0xFF) && $end >= ($codepoint | 0xFF)) {
                    $pages[$page] = true;
                    $codepoint |= 0xFF;
                    continue;
                }
                if (!isset($pages[$page])) {
                    $pages[$page] = [0, 0, 0, 0, 0, 0, 0, 0];
                }
                $pages[$page][$codepoint >> 5 & 7] |= 1 << ($codepoint & 31);
            }
        }
        self::$pages[$property] = $pages;
    }

    /**
     * The index of the last bound at or before a code point.
     */
    private static function run(array $bounds, $codepoint)
    {
        $low = -1;
        $high = \count($bounds) - 1;
        while ($low < $high) {
            $middle = ($low + $high + 1) >> 1;
            if ($bounds[$middle] <= $codepoint) {
                $low = $middle;
            } else {
                $high = $middle - 1;
            }
        }

        return $low;
    }

    private static function isControl($codepoint)
    {
        return $codepoint <= 0x9F && ($codepoint <= 0x1F || 0x7F <= $codepoint);
    }

    private static function isAsciiSpace($codepoint)
    {
        return 0x09 <= $codepoint && $codepoint <= 0x1F && ($codepoint <= 0x0D || 0x1C <= $codepoint);
    }

    /**
     * The code point of an integer, or of a string of one UTF-8 character, null otherwise.
     */
    private static function codePoint($codepoint)
    {
        if (\is_int($codepoint)) {
            return 0 <= $codepoint && $codepoint <= 0x10FFFF ? $codepoint : null;
        }
        $codepoint = (string) $codepoint;
        if (isset(self::$decoded[$codepoint])) {
            return self::$decoded[$codepoint];
        }
        if (null === $decoded = self::decode($codepoint)) {
            return null;
        }
        // At most as many as the characters of a page of text in many scripts
        if (\count(self::$decoded) >= 0x10000) {
            self::$decoded = [];
        }

        return self::$decoded[$codepoint] = $decoded;
    }

    /**
     * The code point of a string of one UTF-8 character, null otherwise.
     */
    private static function decode($codepoint)
    {
        switch (\strlen($codepoint)) {
            case 1:
                return ($byte = \ord($codepoint)) < 0x80 ? $byte : null;
            case 2:
                $byte = \ord($codepoint[0]);
                $next = \ord($codepoint[1]);

                return 0xC2 <= $byte && $byte <= 0xDF && 0x80 === ($next & 0xC0) ? ($byte & 0x1F) << 6 | $next & 0x3F : null;
            case 3:
                $byte = \ord($codepoint[0]);
                $second = \ord($codepoint[1]);
                $third = \ord($codepoint[2]);
                if ($byte < 0xE0 || $byte > 0xEF || 0x80 !== ($second & 0xC0) || 0x80 !== ($third & 0xC0) || (0xE0 === $byte && $second < 0xA0) || (0xED === $byte && $second > 0x9F)) {
                    return null;
                }

                return ($byte & 0x0F) << 12 | ($second & 0x3F) << 6 | $third & 0x3F;
            case 4:
                $byte = \ord($codepoint[0]);
                $second = \ord($codepoint[1]);
                $third = \ord($codepoint[2]);
                $fourth = \ord($codepoint[3]);
                if ($byte < 0xF0 || $byte > 0xF4 || 0x80 !== ($second & 0xC0) || 0x80 !== ($third & 0xC0) || 0x80 !== ($fourth & 0xC0) || (0xF0 === $byte && $second < 0x90) || (0xF4 === $byte && $second > 0x8F)) {
                    return null;
                }

                return ($byte & 0x07) << 18 | ($second & 0x3F) << 12 | ($third & 0x3F) << 6 | $fourth & 0x3F;
        }

        return null;
    }
}
